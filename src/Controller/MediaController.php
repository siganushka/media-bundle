<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Siganushka\MediaBundle\Entity\Media;
use Siganushka\MediaBundle\Event\MediaSaveEvent;
use Siganushka\MediaBundle\Form\MediaUploadType;
use Siganushka\MediaBundle\Repository\MediaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\Util\FormUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class MediaController extends AbstractController
{
    public function __construct(protected readonly MediaRepository $mediaRepository)
    {
    }

    #[Route('/media', methods: 'GET')]
    public function getCollection(Request $request, PaginatorInterface $paginator): Response
    {
        $queryBuilder = $this->mediaRepository->createQueryBuilder('m');

        $page = $request->query->getInt('page', 1);
        $size = $request->query->getInt('size', 10);

        $pagination = $paginator->paginate($queryBuilder, $page, $size);

        return $this->createResponse($pagination);
    }

    #[Route('/media', methods: 'POST')]
    public function postCollection(Request $request, EventDispatcherInterface $eventDispatcher, EntityManagerInterface $entityManager): Response
    {
        // @see https://github.com/symfony/symfony/blob/7.3/src/Symfony/Component/Form/Extension/HttpFoundation/HttpFoundationRequestHandler.php#L39
        $formData = FormUtil::mergeParamsAndFiles(
            $request->request->all(),
            $request->files->all(),
        );

        $form = $this->createForm(MediaUploadType::class);
        $form->submit($formData);

        if (!$form->isValid()) {
            /** @var FormError */
            $error = $form->getErrors(true, true)->current();
            throw new BadRequestHttpException(\sprintf('[%s] %s', $error->getOrigin()?->getName() ?? 'form', $error->getMessage()));
        }

        /** @var array */
        $data = $form->getData();

        $event = new MediaSaveEvent(...$data);
        $eventDispatcher->dispatch($event);

        if (!$media = $event->getMedia()) {
            throw new \RuntimeException('Unable to save file.');
        }

        $entityManager->persist($media);
        $entityManager->flush();

        return $this->createResponse($media);
    }

    #[Route('/media/{hash}', methods: 'GET')]
    public function getItem(string $hash): Response
    {
        $entity = $this->mediaRepository->findOneByHash($hash)
            ?? throw $this->createNotFoundException();

        return $this->createResponse($entity);
    }

    #[Route('/media/{hash}', methods: 'DELETE')]
    public function deleteItem(EntityManagerInterface $entityManager, string $hash): Response
    {
        $entity = $this->mediaRepository->findOneByHash($hash)
            ?? throw $this->createNotFoundException();

        $entityManager->remove($entity);
        $entityManager->flush();

        // 204 No Content
        return $this->createResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @param PaginationInterface<int, mixed>|Media|null $data
     */
    protected function createResponse(PaginationInterface|Media|null $data, int $statusCode = Response::HTTP_OK, array $headers = []): Response
    {
        $attributes = [
            'hash', 'url', 'name', 'extension', 'mime', 'size', 'width', 'height', 'image', 'video', 'createdAt',
        ];

        return $this->json($data, $statusCode, $headers, compact('attributes'));
    }
}

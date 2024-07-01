<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Controller;

use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Siganushka\MediaBundle\ChannelInterface;
use Siganushka\MediaBundle\Entity\Media;
use Siganushka\MediaBundle\Event\MediaSaveEvent;
use Siganushka\MediaBundle\Form\MediaUploadType;
use Siganushka\MediaBundle\Repository\MediaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
#[Route('/media')]
class MediaController extends AbstractController
{
    public function __construct(protected MediaRepository $mediaRepository)
    {
    }

    #[Route(methods: 'GET')]
    public function getCollection(Request $request, PaginatorInterface $paginator): Response
    {
        $queryBuilder = $this->mediaRepository->createQueryBuilder('m');

        $page = $request->query->getInt('page', 1);
        $size = $request->query->getInt('size', 10);

        $pagination = $paginator->paginate($queryBuilder, $page, $size);

        return $this->createResponse($pagination);
    }

    #[Route(methods: 'POST')]
    public function postCollection(Request $request, EventDispatcherInterface $eventDispatcher, EntityManagerInterface $entityManager): Response
    {
        $formData = array_replace_recursive(
            $request->request->all(),
            $request->files->all(),
        );

        $form = $this->createForm(MediaUploadType::class);
        $form->submit($formData);

        if (!$form->isValid()) {
            $error = $form->getErrors(true, true)->current();
            if ($error instanceof FormError) {
                throw new BadRequestHttpException($error->getMessage());
            }
        }

        /** @var array{ channel: ChannelInterface, file: UploadedFile } */
        $data = $form->getData();

        $event = new MediaSaveEvent($data['channel'], $data['file']);
        $eventDispatcher->dispatch($event);

        $media = $event->getMedia();
        if (null === $media) {
            throw new \RuntimeException('Unable to save file.');
        }

        $entityManager->persist($media);
        $entityManager->flush();

        return $this->createResponse($media);
    }

    #[Route('/{hash}', methods: 'GET')]
    public function getItem(string $hash): Response
    {
        $entity = $this->mediaRepository->findOneByHash($hash);
        if (!$entity) {
            throw $this->createNotFoundException(sprintf('Resource #%s not found.', $hash));
        }

        return $this->createResponse($entity);
    }

    #[Route('/{hash}', methods: 'DELETE')]
    public function deleteItem(EntityManagerInterface $entityManager, string $hash): Response
    {
        $entity = $this->mediaRepository->findOneByHash($hash);
        if (!$entity) {
            throw $this->createNotFoundException(sprintf('Resource #%s not found.', $hash));
        }

        try {
            $entityManager->remove($entity);
            $entityManager->flush();
        } catch (ForeignKeyConstraintViolationException $th) {
            throw new BadRequestHttpException('Unable to delete resource.');
        }

        // 204 no content response
        return $this->createResponse(null, Response::HTTP_NO_CONTENT);
    }

    protected function createResponse(PaginationInterface|Media|null $data, int $statusCode = Response::HTTP_OK, array $headers = []): Response
    {
        $attributes = [
            'hash', 'url', 'name', 'extension', 'mimeType', 'size', 'sizeStr',
            'width', 'height', 'image', 'updatedAt', 'createdAt',
        ];

        return $this->json($data, $statusCode, $headers, compact('attributes'));
    }
}

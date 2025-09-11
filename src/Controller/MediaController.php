<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Siganushka\GenericBundle\Response\ProblemJsonResponse;
use Siganushka\MediaBundle\Form\MediaUploadType;
use Siganushka\MediaBundle\MediaManagerInterface;
use Siganushka\MediaBundle\Repository\MediaRepository;
use Siganushka\MediaBundle\Serializer\Normalizer\MediaNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Util\FormUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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
    public function postCollection(Request $request, EntityManagerInterface $entityManager, MediaManagerInterface $mediaManager): Response
    {
        $formData = FormUtil::mergeParamsAndFiles($request->request->all(), $request->files->all());

        $form = $this->createForm(MediaUploadType::class);
        $form->submit($formData);

        if (!$form->isValid()) {
            return $this->createFormErrorResponse($form, Response::HTTP_BAD_REQUEST);
        }

        /** @var array */
        $data = $form->getData();
        $entity = $mediaManager->save(...$data);

        $entityManager->persist($entity);
        $entityManager->flush();

        return $this->createResponse($entity);
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
        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    protected function createFormErrorResponse(FormInterface $form, int $statusCode): Response
    {
        /** @var FormError */
        $error = $form->getErrors(true, true)->current();

        $origin = $error->getOrigin();
        if ($origin && $origin->isRoot()) {
            return new ProblemJsonResponse($error->getMessage(), $statusCode);
        }

        return new ProblemJsonResponse(\sprintf('[%s] %s', $origin?->getName() ?? 'form', $error->getMessage()), $statusCode);
    }

    protected function createResponse(mixed $data, int $statusCode = Response::HTTP_OK, array $headers = []): Response
    {
        return $this->json($data, $statusCode, $headers, [
            MediaNormalizer::AS_REFERENCE => false,
        ]);
    }
}

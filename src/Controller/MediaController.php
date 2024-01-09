<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Controller;

use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Context\Context;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Knp\Component\Pager\PaginatorInterface;
use Siganushka\MediaBundle\Event\MediaSaveEvent;
use Siganushka\MediaBundle\Form\MediaUploadType;
use Siganushka\MediaBundle\Repository\MediaRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class MediaController extends AbstractFOSRestController
{
    protected MediaRepository $mediaRepository;

    public function __construct(MediaRepository $mediaRepository)
    {
        $this->mediaRepository = $mediaRepository;
    }

    /**
     * @Route("/media", methods={"GET"})
     */
    public function getCollection(Request $request, PaginatorInterface $paginator): Response
    {
        $queryBuilder = $this->mediaRepository->createQueryBuilder('m');

        $page = $request->query->getInt('page', 1);
        $size = $request->query->getInt('size', 10);

        $pagination = $paginator->paginate($queryBuilder, $page, $size);

        return $this->viewResponse($pagination);
    }

    /**
     * @Route("/media", methods={"POST"})
     */
    public function postCollection(Request $request, EventDispatcherInterface $eventDispatcher, EntityManagerInterface $entityManager)
    {
        $formData = array_replace_recursive(
            $request->request->all(),
            $request->files->all(),
        );

        $form = $this->createForm(MediaUploadType::class);
        $form->submit($formData);

        if (!$form->isValid()) {
            throw new BadRequestHttpException($form->getErrors(true, true)->current()->getMessage());
        }

        $channel = $form['channel']->getData();
        $file = $form['file']->getData();

        $event = new MediaSaveEvent($channel, $file);
        $eventDispatcher->dispatch($event);

        $media = $event->getMedia();
        if (null === $media) {
            throw new \RuntimeException('Unable to save file.');
        }

        $entityManager->persist($media);
        $entityManager->flush();

        return $this->viewResponse($media);
    }

    /**
     * @Route("/media/{hash<\w>}", methods={"GET"})
     */
    public function getItem(string $hash): Response
    {
        $entity = $this->mediaRepository->findOneByHash($hash);
        if (!$entity) {
            throw $this->createNotFoundException(sprintf('Resource #%s not found.', $hash));
        }

        return $this->viewResponse($entity);
    }

    /**
     * @Route("/media/{hash<\w>}", methods={"DELETE"})
     */
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
        return $this->viewResponse(null, Response::HTTP_NO_CONTENT);
    }

    protected function viewResponse($data = null, int $statusCode = null, array $headers = []): Response
    {
        $attributes = [
            'hash', 'url', 'name', 'extension', 'mimeType', 'bytes', 'width', 'height', 'image',
            'updatedAt', 'createdAt',
        ];

        $context = new Context();
        $context->setAttribute('attributes', $attributes);

        $view = $this->view($data, $statusCode, $headers);
        $view->setContext($context);

        return $this->handleView($view);
    }
}

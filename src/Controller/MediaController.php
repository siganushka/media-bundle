<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Siganushka\GenericBundle\Serializer\Normalizer\FormErrorNormalizer;
use Siganushka\MediaBundle\Form\MediaUploadType;
use Siganushka\MediaBundle\MediaManagerInterface;
use Siganushka\MediaBundle\Repository\MediaRepository;
use Siganushka\MediaBundle\Serializer\Normalizer\MediaNormalizer;
use Siganushka\MediaBundle\Utils\FileUtils;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\Util\FormUtil;
use Symfony\Component\Form\Util\ServerParams;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

class MediaController extends AbstractController
{
    public function __construct(private readonly MediaRepository $mediaRepository)
    {
    }

    public function getCollection(PaginatorInterface $paginator): Response
    {
        $queryBuilder = $this->mediaRepository->createQueryBuilderWithOrderBy('m');
        $pagination = $paginator->paginate($queryBuilder);

        return $this->json($pagination, context: [
            AbstractNormalizer::GROUPS => ['media.collection'],
            MediaNormalizer::AS_REFERENCE => false,
        ]);
    }

    public function postCollection(Request $request, EntityManagerInterface $entityManager, MediaManagerInterface $mediaManager, #[Autowire(service: 'form.server_params')] ServerParams $serverParams): Response
    {
        $submittedData = $this->parseSubmittedData($request);

        $form = $this->createForm(MediaUploadType::class);
        $form->submit($submittedData);

        if ($serverParams->hasPostMaxSizeBeenExceeded()) {
            $form->addError(new FormError($form->getConfig()->getOption('upload_max_size_message')()));
        }

        if (!$form->isValid()) {
            return $this->json($form, Response::HTTP_BAD_REQUEST, context: [
                FormErrorNormalizer::STATUS => Response::HTTP_BAD_REQUEST,
                FormErrorNormalizer::WITH_ERRORS => false,
            ]);
        }

        /** @var array */
        $data = $form->getData();

        $entity = $mediaManager->save(...$data);
        $status = $entityManager->contains($entity) ? Response::HTTP_OK : Response::HTTP_CREATED;

        $entityManager->persist($entity);
        $entityManager->flush();

        return $this->json($entity, $status, context: [
            AbstractNormalizer::GROUPS => ['media.item'],
            MediaNormalizer::AS_REFERENCE => false,
        ]);
    }

    public function getItem(string $hash): Response
    {
        $entity = $this->mediaRepository->findOneByHash($hash)
            ?? throw $this->createNotFoundException();

        return $this->json($entity, context: [
            AbstractNormalizer::GROUPS => ['media.item'],
            MediaNormalizer::AS_REFERENCE => false,
        ]);
    }

    public function deleteItem(EntityManagerInterface $entityManager, string $hash): Response
    {
        $entity = $this->mediaRepository->findOneByHash($hash)
            ?? throw $this->createNotFoundException();

        $entityManager->remove($entity);
        $entityManager->flush();

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    private function parseSubmittedData(Request $request): array
    {
        $submittedData = array_replace($request->query->all(), $request->request->all());

        if ($request->files->has('file')) {
            $submittedData = FormUtil::mergeParamsAndFiles($submittedData, $request->files->all());
        } elseif ($content = $request->getContent()) {
            $submittedData['file'] = new File(FileUtils::createFromContent($content));
        }

        return $submittedData;
    }
}

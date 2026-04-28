<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Controller;

use Siganushka\MediaBundle\Repository\MediaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MediaRefController extends AbstractController
{
    public function __invoke(Request $request, MediaRepository $repository, string $hash): Response
    {
        $entity = $repository->findOneByHash($hash);
        if (!$entity || !$url = $entity->getUrl()) {
            throw $this->createNotFoundException();
        }

        // $url .= sprintf('?t=%d', $entity->getCreatedAt()->getTimestamp());

        $response = new RedirectResponse($url);
        $response->setPublic();
        $response->setMaxAge(31536000);
        $response->setSharedMaxAge(31536000);
        $response->setLastModified($entity->getCreatedAt());
        $response->setEtag(md5($url));

        if ($response->isNotModified($request)) {
            return $response;
        }

        // do something...

        return $response;
    }
}

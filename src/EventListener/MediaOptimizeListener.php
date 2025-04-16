<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\EventListener;

use Psr\Log\LoggerInterface;
use Siganushka\MediaBundle\Event\MediaSaveEvent;
use Spatie\ImageOptimizer\OptimizerChainFactory;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MediaOptimizeListener implements EventSubscriberInterface
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function onMediaSave(MediaSaveEvent $event): void
    {
        $channel = $event->getChannel();
        if (null === $channel->optimize) {
            return;
        }

        try {
            $optimizerChain = OptimizerChainFactory::create(['quality' => $channel->optimize]);
            $optimizerChain->useLogger($this->logger);
            $optimizerChain->optimize($event->getFile()->getPathname());
        } catch (\Throwable $th) {
            $this->logger->error('Error on optimize file.', [
                'msg' => $th->getMessage(),
            ]);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MediaSaveEvent::class => ['onMediaSave', 8],
        ];
    }
}

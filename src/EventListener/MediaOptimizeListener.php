<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\EventListener;

use Psr\Log\LoggerInterface;
use Siganushka\MediaBundle\Event\MediaSaveEvent;
use Spatie\ImageOptimizer\OptimizerChainFactory;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(MediaSaveEvent::class, priority: 4)]
class MediaOptimizeListener
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function __invoke(MediaSaveEvent $event): void
    {
        $channel = $event->getChannel();
        if (null === $channel->optimizeToQuality) {
            return;
        }

        try {
            $optimizerChain = OptimizerChainFactory::create(['quality' => $channel->optimizeToQuality]);
            $optimizerChain->useLogger($this->logger);
            $optimizerChain->optimize($event->getFile()->getPathname());
        } catch (\Throwable $th) {
            $this->logger->error('Error on optimize file.', [
                'msg' => $th->getMessage(),
            ]);
        }
    }
}

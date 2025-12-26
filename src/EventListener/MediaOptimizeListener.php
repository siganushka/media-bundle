<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\EventListener;

use Psr\Log\LoggerInterface;
use Siganushka\MediaBundle\Event\MediaSaveEvent;
use Spatie\ImageOptimizer\OptimizerChainFactory;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(priority: 8)]
class MediaOptimizeListener
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function __invoke(MediaSaveEvent $event): void
    {
        $rule = $event->getRule();
        if (null === $rule->optimizeToQuality) {
            return;
        }

        try {
            $optimizer = OptimizerChainFactory::create(['quality' => $rule->optimizeToQuality]);
            $optimizer->useLogger($this->logger);
            $optimizer->optimize($event->getFile()->getPathname());
        } catch (\Throwable $th) {
            $this->logger->error('Error on optimize file.', [
                'msg' => $th->getMessage(),
            ]);
        }
    }
}

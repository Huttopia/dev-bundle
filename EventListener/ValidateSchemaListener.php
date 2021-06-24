<?php

declare(strict_types=1);

namespace steevanb\DevBundle\EventListener;

use steevanb\DevBundle\Service\ValidateSchemaService;
use Symfony\Component\HttpKernel\{
    Event\RequestEvent,
    HttpKernelInterface
};

class ValidateSchemaListener
{
    /** @var ValidateSchemaService */
    protected $validateSchema;

    /** @var array */
    protected $disabledUrls = array();

    public function __construct(ValidateSchemaService $validateSchema, array $disabledUrls)
    {
        $this->validateSchema = $validateSchema;
        $this->disabledUrls = $disabledUrls;
    }

    public function validateSchema(RequestEvent $event): void
    {
        if ($this->needValidate($event)) {
            $this->validateSchema->assertSchemaIsValid();
        }
    }

    protected function needValidate(RequestEvent $event): bool
    {
        $return = false;

        if ($event->getRequestType() === HttpKernelInterface::MASTER_REQUEST) {
            $return = true;
            $urlParts = parse_url($event->getRequest()->getUri());
            foreach ($this->disabledUrls as $disabledUrl) {
                if (substr($urlParts['path'], 0, strlen($disabledUrl)) === $disabledUrl) {
                    $return = false;
                    continue;
                }
            }
        }

        return $return;
    }
}

<?php

declare(strict_types=1);

namespace steevanb\DevBundle\EventListener;

use steevanb\DevBundle\Exception\TranslationsNotFoundException;
use Symfony\Component\Translation\DataCollectorTranslator;

class TranslationsNotFoundListener
{
    /** @var DataCollectorTranslator */
    protected $dataCollectorTranslator;

    /** @var bool */
    protected $allowFallbacks = false;

    public function __construct(DataCollectorTranslator $dataCollectorTranslator)
    {
        $this->dataCollectorTranslator = $dataCollectorTranslator;
    }

    public function setAllowFallbacks(bool $allow): self
    {
        $this->allowFallbacks = $allow;

        return $this;
    }

    public function assertAllTranslationsFound(): self
    {
        $missings = array();
        foreach ($this->dataCollectorTranslator->getCollectedMessages() as $message) {
            if ($message['state'] === DataCollectorTranslator::MESSAGE_MISSING) {
                $missings[] = $message;
            } elseif (
                $message['state'] === DataCollectorTranslator::MESSAGE_EQUALS_FALLBACK
                && $this->allowFallbacks === false
                && $this->isSameLanguage((string) $message['locale'], (string) $message['fallbackLocale']) === false
            ) {
                $message['locale'] = 'fallback ' . $message['fallbackLocale'] . ' found for ' . $message['locale'] . ' ';
                $message['locale'] .= 'but not allowed by config dev.translation.allow_fallbacks)';
                $missings[] = $message;
            }
        }

        if (count($missings) > 0) {
            throw new TranslationsNotFoundException($missings);
        }

        return $this;
    }

    /**
     * A translation found in the language parent (en_US => en, es_AR => es) is the expected one, not a fallback :
     * regional catalogues only hold their own overrides.
     */
    protected function isSameLanguage(string $locale, string $fallbackLocale): bool
    {
        return \Locale::getPrimaryLanguage($locale) === \Locale::getPrimaryLanguage($fallbackLocale);
    }
}

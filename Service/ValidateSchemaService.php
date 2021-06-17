<?php

declare(strict_types=1);

namespace steevanb\DevBundle\Service;

use Doctrine\ORM\Tools\SchemaValidator;
use steevanb\DevBundle\Exception\InvalidMappingException;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\KernelInterface;

class ValidateSchemaService
{
    /** @var Registry */
    protected $doctrine;

    /** @var KernelInterface */
    protected $kernel;

    /** @var array */
    protected $excludedEntities = array();

    /** @var array */
    protected $excludedProperties = array();

    /** @var string[] */
    protected $mappingPaths = [];

    /** Messages from Doctrine\ORM\Tools\SchemaValidator */
    protected $schemaValidatorMessages = array(
        'The field \'%s\' ',
        'The field %s ',
        'The association %s ',
        'Cannot map association \'%s\' ',
        'The mappings %s and ',
        'If association %s '
    );

    public function __construct(Registry $doctrine, KernelInterface $kernel)
    {
        $this->doctrine = $doctrine;
        $this->kernel = $kernel;
    }

    public function setExcludes(array $excludes): self
    {
        $this->excludedEntities = array();
        $this->excludedProperties = array();

        foreach ($excludes as $exclude) {
            if (strpos($exclude, '#') === false) {
                $this->excludedEntities[] = $exclude;
            } else {
                $this->excludedProperties[] = $exclude;
            }
        }

        return $this;
    }

    public function addMappingPath(string $path): self
    {
        if (in_array($path, $this->mappingPaths) === false) {
            $this->mappingPaths[] = $path;
        }

        return $this;
    }

    public function addMappingBundle(string $bundle): self
    {
        $path = $this->kernel->getBundle($bundle)->getPath();
        $path .= DIRECTORY_SEPARATOR . 'Resources';
        $path .= DIRECTORY_SEPARATOR . 'config';
        $path .= DIRECTORY_SEPARATOR . 'doctrine';
        $this->addMappingPath($path);

        return $this;
    }

    public function assertSchemaIsValid(): self
    {
        if ($this->needValidate()) {
            foreach ($this->doctrine->getManagers() as $managerName => $manager) {
                $validator = new SchemaValidator($manager);
                foreach ($validator->validateMapping() as $entity => $errors) {
                    $this->assertAuthorizedMappingErrors($managerName, $entity, $errors);
                }
            }

            $this->saveLastValidateTimestamp();
        }

        return $this;
    }

    protected function getLastMappingValidateFilePath(): string
    {
        return $this->kernel->getCacheDir() . DIRECTORY_SEPARATOR . 'dev_bundle_last_mapping_validate';
    }

    protected function needValidate(): bool
    {
        $lastValidateTimestamp = $this->getLastValidateTimestamp();
        $return = false;

        foreach ($this->mappingPaths as $path) {
            if (is_dir($path)) {
                $finder = new Finder();
                foreach ($finder->in($path)->files() as $file) {
                    if ($file->getMTime() >= $lastValidateTimestamp) {
                        $return = true;
                        continue 2;
                    }
                }
            }
        }

        return $return;
    }

    protected function saveLastValidateTimestamp(): self
    {
        $filePath = $this->getLastMappingValidateFilePath();
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        file_put_contents($filePath, '');

        return $this;
    }

    protected function getLastValidateTimestamp(): int
    {
        $filePath = $this->getLastMappingValidateFilePath();

        return file_exists($filePath) ? filemtime($filePath) : 0;
    }

    protected function assertAuthorizedMappingErrors(string $managerName, string $entityName, array $errors): self
    {
        if (in_array($entityName, $this->excludedEntities) === false) {
            foreach ($errors as $error) {
                foreach ($this->excludedProperties as $excludedProperty) {
                    foreach ($this->schemaValidatorMessages as $schemaValidatorMessage) {
                        $messageStart = sprintf($schemaValidatorMessage, $excludedProperty);
                        if ($messageStart === substr($error, 0, strlen($messageStart))) {
                            continue 3;
                        }
                    }
                }

                throw new InvalidMappingException($managerName, $error);
            }
        }

        return $this;
    }
}

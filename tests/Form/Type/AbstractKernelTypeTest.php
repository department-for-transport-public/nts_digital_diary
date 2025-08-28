<?php

namespace App\Tests\Form\Type;

use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormRegistryInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\PreloadedExtension;

abstract class AbstractKernelTypeTest extends KernelTestCase
{
    protected AbstractDatabaseTool $databaseTool;
    protected EntityManagerInterface $entityManager;
    protected FormFactoryInterface $formFactory;
    protected FormRegistryInterface $formRegistry;
    private array $preloadTypes;

    public function setUp(): void
    {
        parent::setUp();

        $this->databaseTool = $this->getService(DatabaseToolCollection::class, DatabaseToolCollection::class)->get();

        $this->entityManager = $this->getService(EntityManagerInterface::class, EntityManagerInterface::class);
        $this->formFactory = $this->getService('form.factory', FormFactoryInterface::class);
        $this->formRegistry = $this->getService('form.registry', FormRegistryInterface::class);

        $this->preloadTypes = [];
    }

    /**
     * @template X of object
     * @param class-string<X> $class
     * @return X
     */
    protected function getService(string $id, string $class)
    {
        $service = self::getContainer()->get($id);

        if (!$service instanceof $class) {
            $actualType = $service::class;
            $this->fail("Expected service '$id' to be an instance of $class, but got $actualType instead");
        }

        return $service;
    }

    protected function queueFormTypeForPreload(FormTypeInterface $form): static
    {
        $this->preloadTypes[] = $form;
        return $this;
    }

    protected function preloadFormTypes(): static
    {
        $extensions = $this->formRegistry->getExtensions();
        array_unshift($extensions, new PreloadedExtension($this->preloadTypes, []));

        try {
            // It's a private property...
            (new \ReflectionClass($this->formRegistry))
                ->getProperty('extensions')
                ->setValue($this->formRegistry, $extensions);
        } catch (\ReflectionException) {
            $this->fail('Failed to inject form extension');
        }

        return $this;
    }
}

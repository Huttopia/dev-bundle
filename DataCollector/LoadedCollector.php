<?php

declare(strict_types=1);

namespace steevanb\DevBundle\DataCollector;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\{
    Request,
    Response
};
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

class LoadedCollector extends DataCollector
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getName(): string
    {
        return 'loaded_data_collector';
    }

    public function collect(
        Request $request,
        Response $response,
        ?\Throwable $exception = null
    ): void {
        $this->data = [
            'declaredClasses' => get_declared_classes(),
            'declaredInterfaces' => get_declared_interfaces(),
            'declaredTraits' => get_declared_traits(),
            'definedConstants' => get_defined_constants(),
            'definedFunctions' => get_defined_functions()['user'],
            'services' => $this->container->getServiceIds(),
            'instantiatedServices' => $this->getInstantiatedServicesData(),
            'parameters' => $this->container->getParameterBag()->all(),
            'listeners' => $this->getListenersData()
        ];
    }

    public function reset(): void
    {
        $this->data = [];
    }

    public function getDeclaredClasses(): array
    {
        static $sorted = false;
        if ($sorted === false) {
            $sorted = true;
            sort($this->data['declaredClasses']);
        }

        return $this->data['declaredClasses'];
    }

    public function countDeclaredClasses(): int
    {
        return count($this->data['declaredClasses']);
    }

    public function getDeclaredInterfaces(): array
    {
        static $sorted = false;
        if ($sorted === false) {
            $sorted = true;
            sort($this->data['declaredInterfaces']);
        }

        return $this->data['declaredInterfaces'];
    }

    public function countDeclaredInterfaces(): int
    {
        return count($this->data['declaredInterfaces']);
    }

    public function getDeclaredTraits(): array
    {
        static $sorted = false;
        if ($sorted === false) {
            $sorted = true;
            sort($this->data['declaredTraits']);
        }

        return $this->data['declaredTraits'];
    }

    public function countDeclaredTraits(): int
    {
        return count($this->data['declaredTraits']);
    }

    public function getDefinedConstants(): array
    {
        static $sorted = false;
        if ($sorted === false) {
            $sorted = true;
            ksort($this->data['definedConstants']);
        }

        return $this->data['definedConstants'];
    }

    public function countDefinedConstants(): int
    {
        return count($this->data['definedConstants']);
    }

    public function getDefinedFunctions(): array
    {
        static $sorted = false;
        if ($sorted === false) {
            $sorted = true;
            sort($this->data['definedFunctions']);
        }

        return $this->data['definedFunctions'];
    }

    public function countDefinedFunctions(): int
    {
        return count($this->data['definedFunctions']);
    }

    public function getServiceIds(): array
    {
        static $sorted = false;
        if ($sorted === false) {
            $sorted = true;
            sort($this->data['services']);
        }

        return $this->data['services'];
    }

    public function countServiceIds(): int
    {
        return count($this->data['services']);
    }

    public function getParameters(): array
    {
        static $sorted = false;
        if ($sorted === false) {
            $sorted = true;
            ksort($this->data['parameters']);
        }

        return $this->data['parameters'];
    }

    public function countParameters(): int
    {
        return count($this->data['parameters']);
    }

    public function getListeners(): array
    {
        static $sorted = false;
        if ($sorted === false) {
            $sorted = true;
            ksort($this->data['listeners']);
        }

        return $this->data['listeners'];
    }

    public function countListeners(): int
    {
        $return = 0;
        foreach ($this->getListeners() as $listeners) {
            $return += count($listeners);
        }

        return $return;
    }

    public function getInstantiatedServices(): array
    {
        static $sorted = false;
        if ($sorted === false) {
            $sorted = true;
            ksort($this->data['instantiatedServices']);
        }

        return $this->data['instantiatedServices'];
    }

    public function countInstantiatedServices(): int
    {
        return count($this->data['instantiatedServices']);
    }

    protected function getListenersData(): array
    {
        $return = [];
        foreach ($this->container->get('event_dispatcher')->getListeners() as $eventId => $listeners) {
            $return[$eventId] = [];
            foreach ($listeners as $listener) {
                if (is_array($listener)) {
                    // Listener is [object, method] or [class, method]
                    if (is_object($listener[0])) {
                        $return[$eventId][] = $listener[0] instanceof \Closure
                            ? 'Closure'
                            : get_class($listener[0]);
                    } else {
                        $return[$eventId][] = $listener[0];
                    }
                } elseif ($listener instanceof \Closure) {
                    $return[$eventId][] = 'Closure';
                } elseif (is_object($listener)) {
                    $return[$eventId][] = get_class($listener);
                } else {
                    $return[$eventId][] = (string) $listener;
                }
            }
        }

        return $return;
    }

    protected function getInstantiatedServicesData(): array
    {
        // Since Symfony 6+, we can't access private services property directly
        // Return an empty array or implement alternative tracking if needed
        return [];
    }
}

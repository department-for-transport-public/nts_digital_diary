<?php


namespace Ghost\GovUkFrontendBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class ContextualOptionsFormListener implements EventSubscriberInterface
{
    /**
     * @var callable
     */
    private $callback;

    /**
     * The callback will be called for each child of the listened form.
     * The callback will receive the following params:
     *  - FormEvent $event
     *  - $name name of the child
     *  - array $options the initial options of the child
     * The callback must return an array with the final options of the child.
     *
     * @param callable $callback
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData',
        ];
    }

    /**
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();

        $children = $form->all();

        foreach ($form as $name => $child) {
            $form->remove($name);
        }

        foreach ($children as $name => $child) {
            $innerType = $child->getConfig()->getType()->getInnerType();
            $form->add(
                $name,
                is_object($innerType) ? get_class($innerType) : $innerType,
                call_user_func($this->callback, $event, $name, $child->getConfig()->getOptions())
            );
        }
    }
}
<?php

namespace Oro\Bundle\ApiBundle\Form;

use Oro\Bundle\ApiBundle\Form\Extension\ValidationExtension;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataHandler;
use Symfony\Component\Form\Extension\Validator\EventListener\ValidationListener;
use Symfony\Component\Form\Extension\Validator\ViolationMapper\ViolationMapper;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Provides functionality to validate any root form.
 */
class FormValidationHandler
{
    /** @var ValidatorInterface */
    protected $validator;

    /** @var CustomizeFormDataHandler */
    private $customizationHandler;

    /**
     * @param ValidatorInterface       $validator
     * @param CustomizeFormDataHandler $customizationHandler
     */
    public function __construct(ValidatorInterface $validator, CustomizeFormDataHandler $customizationHandler)
    {
        $this->validator = $validator;
        $this->customizationHandler = $customizationHandler;
    }

    /**
     * Validates the given form.
     *
     * @param FormInterface $form
     *
     * @throws \InvalidArgumentException if the given for is not root form or it is not submitted yet
     */
    public function validate(FormInterface $form): void
    {
        if (!$form->isRoot()) {
            throw new \InvalidArgumentException('The root form is expected.');
        }
        if (!$form->isSubmitted()) {
            throw new \InvalidArgumentException('The submitted form is expected.');
        }

        /**
         * Mark all children of the processing root form as submitted
         * before start the form validation.
         * Using Form::submit($clearMissing = false) with the setting all fields as submitted
         * is a bit better approach than
         * using Form::submit($clearMissing = true) with a "pre-submit" listener that replaces missing fields
         * in submitted data with its default values from an entity.
         * Using the first approach we can submit all Data API forms
         * with $clearMissing = false and manage the validation just via "enable_full_validation" form option.
         * @link https://symfony.com/doc/current/form/direct_submit.html
         * @see \Symfony\Component\Form\Form::submit
         * @link https://github.com/symfony/symfony/pull/10567
         * @see \Symfony\Component\Form\Extension\Validator\ViolationMapper\ViolationMapper::acceptsErrors
         * @see \Symfony\Component\Form\Extension\Validator\EventListener\ValidationListener
         * @see \Oro\Bundle\ApiBundle\Form\Extension\ValidationExtension
         */
        if ($form->getConfig()->getOption(ValidationExtension::ENABLE_FULL_VALIDATION)) {
            ReflectionUtil::markFormChildrenAsSubmitted($form);
        }

        $validationListener = $this->getValidationListener();
        $event = $this->createFormEvent($form);
        $validationListener->validateForm($event);
        $this->dispatchFinishSubmitEventForChildren($form);
        if ($this->isFinishSubmitEventSupported($form)) {
            $this->dispatchFinishSubmitEvent($event);
        }
    }

    /**
     * @return ValidationListener
     */
    protected function getValidationListener(): ValidationListener
    {
        return new ValidationListener(
            $this->validator,
            new ViolationMapper()
        );
    }

    /**
     * @param FormInterface $form
     *
     * @return FormEvent
     */
    private function createFormEvent(FormInterface $form): FormEvent
    {
        return new FormEvent($form, $form->getViewData());
    }

    /**
     * @param FormInterface $form
     *
     * @return bool
     */
    private function isFinishSubmitEventSupported(FormInterface $form): bool
    {
        return $form->getConfig()->hasAttribute(CustomizeFormDataHandler::API_EVENT_CONTEXT);
    }
    /**
     * @param FormEvent $event
     */
    private function dispatchFinishSubmitEvent(FormEvent $event): void
    {
        $this->customizationHandler->handleFormEvent(CustomizeFormDataContext::EVENT_FINISH_SUBMIT, $event);
    }

    /**
     * @param FormInterface $form
     */
    private function dispatchFinishSubmitEventForChildren(FormInterface $form): void
    {
        /** @var FormInterface $child */
        foreach ($form as $child) {
            if ($child->count() > 0) {
                $this->dispatchFinishSubmitEventForChildren($child);
            }
            if ($this->isFinishSubmitEventSupported($child)) {
                $this->dispatchFinishSubmitEvent($this->createFormEvent($child));
            }
        }
    }
}

<?php

namespace Novactive\Bundle\eZSEOBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class RedirectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'source',
                TextType::class,
                [
                    'required' => true,
                ]
            )
            ->add(
                'destination',
                TextType::class,
                [
                    'required' => true,
                ]
            )
            ->add(
                'type',
                CheckboxType::class,
                [
                    'required' => false,
                    'data'     => true,
                ]
            )
            ->add(
                'save',
                SubmitType::class,
                [
                    'attr' => ['class' => 'btn btn-primary'],
                ]
            );
    }
}

<?php

namespace Novactive\Bundle\eZSEOBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

class ImportUrlsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'file',
                FileType::class,
                ['required' => true]
            )
            ->add(
                'import',
                SubmitType::class,
                ['attr' => ['class' => 'btn btn-primary']]
            );
    }

    public function getBlockPrefix(): string
    {
        return 'novaseo_import_urls';
    }
}

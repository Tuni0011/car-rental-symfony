<?php

namespace App\Form;

use App\Entity\Reservation;
use App\Entity\Car;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Validator\Constraints\File;


class ReservationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('car')

            ->add('fullName')

            ->add('phoneNumber')

            ->add('startDate', DateType::class, [
                'widget' => 'single_text',
            ])

            ->add('endDate', DateType::class, [
                'widget' => 'single_text',
            ])

            ->add('licenseIssueDate', DateType::class, [
                'widget' => 'single_text',
            ])

            ->add('cinImage', FileType::class, [
                'mapped' => false,
                'required' => false,
            ])

            ->add('licenseImage', FileType::class, [
                'mapped' => false,
                'required' => false,
            ]);
    }
}

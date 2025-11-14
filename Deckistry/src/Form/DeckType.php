<?php

namespace App\Form;

use App\Entity\Deck;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DeckType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du deck',
                'attr' => [
                    'placeholder' => 'Super deck du futur 2086',
                    'class' => 'form-control'
                ],
                'required' => true,
            ])
            ->add('format', ChoiceType::class, [
                'label' => 'Format',
                'choices' => [
                    'Commander' => 'Commander',
                    'Standard' => 'Standard',
                ],
                'attr' => ['class' => 'form-select'],
                'required' => true,
            ])
            ->add('isPrivate', ChoiceType::class, [
                'label' => false,
                'choices' => [
                    'Privée' => true,
                    'Public' => false,
                ],
                'expanded' => true,
                'multiple' => false,
                'data' => false,
                'label_attr' => ['class' => 'radio-inline'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Deck::class,
        ]);
    }
}

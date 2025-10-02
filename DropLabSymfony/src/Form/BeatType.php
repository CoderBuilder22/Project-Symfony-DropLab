<?php

namespace App\Form;

use App\Entity\Beat;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class BeatType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a title',
                    ]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'Describe your beat...'
                ]
            ])
            ->add('genre', TextType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a genre',
                    ]),
                ],
            ])
            ->add('price', MoneyType::class, [
                'currency' => 'USD',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a price',
                    ]),
                    new Range([
                        'min' => 0.99,
                        'minMessage' => 'Price must be at least {{ limit }}$',
                    ]),
                ],
            ])
            ->add('bpm', NumberType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter BPM',
                    ]),
                    new Range([
                        'min' => 40,
                        'max' => 300,
                        'minMessage' => 'BPM must be at least {{ limit }}',
                        'maxMessage' => 'BPM cannot be higher than {{ limit }}',
                    ]),
                ],
            ])
            ->add('audioFile', FileType::class, [
                'label' => 'Beat file (MP3/WAV)',
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new File([
                        'maxSize' => '50M',
                        'mimeTypes' => [
                            'audio/mpeg',
                            'audio/wav',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid audio file',
                    ])
                ],
            ])
            ->add('coverImage', FileType::class, [
                'label' => 'Cover Image (JPG/PNG)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image file',
                    ])
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Beat::class,
        ]);
    }
} 
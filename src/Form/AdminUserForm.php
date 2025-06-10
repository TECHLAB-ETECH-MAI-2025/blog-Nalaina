<?php
    namespace App\Form;
    use App\Entity\User;
    use Symfony\Component\Validator\Constraints\Email;
    use Symfony\Component\Validator\Constraints\Length;
    use Symfony\Component\Form\AbstractType;
    use Symfony\Component\Form\Extension\Core\Type\EmailType;
    use Symfony\Component\Form\Extension\Core\Type\PasswordType;
    use Symfony\Component\Form\Extension\Core\Type\TextType;
    use Symfony\Component\Form\FormBuilderInterface;
    use Symfony\Component\OptionsResolver\OptionsResolver;
    use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
    use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
    use Symfony\Component\Validator\Constraints\NotBlank;

    class AdminUserForm extends AbstractType
    {
        public function buildForm(FormBuilderInterface $builder, array $options): void
        {
            $builder
                ->add('firstName', TextType::class, [
                    'label' => 'Prénom',
                    'required' => false,
                    'attr' => [
                        'placeholder' => 'Prénom de l\'utilisateur',
                        'class' => 'form-control',
                    ],
                ])
                ->add('lastName', TextType::class, [
                    'label' => 'Nom',
                    'required' => false,
                    'attr' => [
                        'placeholder' => 'Nom de l\'utilisateur',
                        'class' => 'form-control',
                    ],
                ])
                ->add('email', EmailType::class, [
                    'label' => 'Adresse e-mail',
                    'attr' => [
                        'placeholder' => 'Entrez une adresse e-mail',
                        'class' => 'form-control',
                    ],
                    'constraints' => [
                        new NotBlank([
                            'message' => 'L\'adresse e-mail ne peut pas être vide.',
                        ]),
                        new Email([
                            'message' => 'Veuillez entrer une adresse e-mail valide.',
                        ]),
                    ]

                ])
                ->add('roles', ChoiceType::class, [
                    'label' => 'Rôles',
                    'choices' => [
                        'Utilisateur' => 'ROLE_USER',
                        'Administrateur' => 'ROLE_ADMIN',
                        'Super Administrateur' => 'ROLE_SUPER_ADMIN',
                    ],
                    'multiple' => true,
                    'expanded' => true, // true for checkboxes, false for select
                    'attr' => ['class' => 'form-check-input'],
                    'label_attr' => ['class' => 'form-check-label'],
                ])
                ->add('isVerified', CheckboxType::class, [
                    'label'    => 'Utilisateur vérifié',
                    'required' => false,
                    'attr'     => ['class' => 'form-check-input'],
                    'label_attr' => ['class' => 'form-check-label'],
                ])
                ->add('plainPassword', PasswordType::class, [
                    'label' => 'Mot de passe',
                    'mapped' => false,
                    'required' => $options['is_new_user'],
                    'attr' => [
                        'placeholder' => $options['is_new_user']? 'Mot de passe' : 'Laisser vide pour ne pas modifier le mot de passe', 
                        'class' => 'form-control',
                        'autocomplete' => 'new-password',
                    ],
                    'constraints' => $options['is_new_user'] ? [
                        new NotBlank([
                            'message' => 'Le mot de passe ne peut pas être vide.',
                        ]),
                        new Length([
                            'min' => 8,
                            'minMessage' => 'Le mot de passe doit contenir au moins {{ limit }} caractères.',
                            'max' => 4096,
                        ]),
                    ]: [],
                    // 'first_options'  => ['label' => 'Mot de passe'],
                    // 'second_options' => ['label' => 'Confirmer le mot de passe'],
                    // 'invalid_message' => 'Les mots de passe doivent correspondre.',
                    // 'required' => true,
                ]);
        }

        public function configureOptions(OptionsResolver $resolver): void
        {
            $resolver->setDefaults([
                'data_class' => User::class,
                'is_new_user' => false, 
            ]);
        }
    }
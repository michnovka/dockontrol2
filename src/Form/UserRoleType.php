<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Enum\UserRole;
use App\Entity\User;
use App\Form\Type\SearchableEntityType;
use Elao\Enum\Bridge\Symfony\Form\Type\EnumType;
use InvalidArgumentException;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserRoleType extends AbstractType
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $userRoleChoices = $options['user_role_choices'];
        /** @var User $user */
        $user = $builder->getData();

        $builder->add('role', EnumType::class, [
            'label' => 'User role',
            'class' => UserRole::class,
            'multiple' => false,
            'choices' => $this->getChoicesArrayForUserRole($userRoleChoices),
            'attr' => [
                'class' => 'choices-select',
            ],
        ])
        ->add('landlord', SearchableEntityType::class, [
            'class' => User::class,
            'required' => false,
            'label' => 'Landlord',
            'multiple' => false,
            'placeholder' => 'Landlord',
            'choice_label' => function (User $user) {
                return $user->getTwigDisplayValue();
            },
            'attr' => [
                'data-choices-search-enabled' => 'true',
                'data-choices-search-choices' => 'true',
                'data-clear-choices' => 'true',
            ],
        ]);

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event): void {
            $user = $event->getData();
            if ($user instanceof User) {
                if ($user->getRole() !== UserRole::TENANT) {
                    $user->setLandlord(null);
                } else {
                    $user->setApartment($user->getLandlord()?->getApartment());
                }
            }
        });
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'user_role_choices' => 'all',
        ]);
    }

    /**
     * @return UserRole[]
     */
    private function getChoicesArrayForUserRole(string $userRoleChoices): array
    {
        if ($userRoleChoices === 'limited') {
            return [
                UserRole::TENANT,
                UserRole::LANDLORD,
            ];
        } elseif ($userRoleChoices === 'all') {
            return UserRole::cases();
        } else {
            throw new InvalidArgumentException('Invalid value for option user_role_choices.');
        }
    }
}

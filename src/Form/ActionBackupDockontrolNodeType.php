<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Action;
use App\Entity\ActionBackupDockontrolNode;
use App\Entity\DockontrolNode;
use App\Form\Type\JsonType;
use App\Repository\DockontrolNodeRepository;
use Override;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ActionBackupDockontrolNodeType extends AbstractType
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $parentAction = $options['parentAction'];
        $parentActonNode = $parentAction->getDockontrolNode();

        $builder
            ->add('dockontrolNode', EntityType::class, [
                'class' => DockontrolNode::class,
                'choice_label' => 'name',
                'query_builder' => function (DockontrolNodeRepository $er) use ($parentAction, $parentActonNode) {
                    return $er->createQueryBuilder('d')
                        ->leftJoin('d.actionBackupDockontrolNodes', 'ab', 'WITH', 'ab.parentAction = :parentAction')
                        ->where('ab.id IS NULL')
                        ->andWhere('d != :parentActonNode')
                        ->setParameter('parentAction', $parentAction)
                        ->setParameter('parentActonNode', $parentActonNode);
                },
                'attr' => [
                    'class' => 'choices-select',
                ],
            ])
            ->add('actionPayload', JsonType::class, [
                'label' => 'Action payload',
                'required' => false,
            ])
        ;
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ActionBackupDockontrolNode::class,
            'parentAction' => Action::class,
        ]);
    }
}

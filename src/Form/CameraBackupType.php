<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Camera;
use App\Entity\CameraBackup;
use App\Entity\DockontrolNode;
use App\Form\Type\JsonType;
use App\Repository\DockontrolNodeRepository;
use Override;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CameraBackupType extends AbstractType
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $parentCamera = $options['parentCamera'];
        $parentDockontrolNode = $parentCamera->getDockontrolNode();

        $builder
            ->add('dockontrolNode', EntityType::class, [
                'class' => DockontrolNode::class,
                'choice_label' => 'name',
                'query_builder' => function (DockontrolNodeRepository $er) use ($parentDockontrolNode, $parentCamera) {
                    return $er->createQueryBuilder('d')
                        ->leftJoin('d.cameraBackups', 'cb', 'WITH', 'cb.parentCamera = :parentCamera')
                        ->andWhere('cb.id IS NULL')
                        ->andWhere('d != :parentDockontrolNode')
                        ->setParameter('parentDockontrolNode', $parentDockontrolNode)
                        ->setParameter('parentCamera', $parentCamera);
                },
                'attr' => [
                    'class' => 'choices-select',
                ],
            ])
            ->add('dockontrolNodePayload', JsonType::class, [
                'label' => 'DOCKontrol node payload',
                'required' => false,
            ])
        ;
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CameraBackup::class,
            'parentCamera' => Camera::class,
        ]);
    }
}

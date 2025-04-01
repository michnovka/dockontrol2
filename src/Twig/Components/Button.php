<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Entity\Button as ButtonEntity;
use App\Entity\Camera;
use App\Entity\Enum\ButtonStyle;
use App\Entity\Enum\ButtonType;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class Button
{
    public string $type = 'button';
    public ?ButtonEntity $buttonObj = null;

    public string $id;
    public string $name;
    public ?string $value = null;
    public ?string $text = null;
    public string $formSubmitButton = 'true';
    public bool $allowCamera = false;

    public bool $isDraggable = false;

    public bool $isRelative = false;

    public bool $showEditAndDeleteIcon = false;

    public ?string $customName = null;

    public ?ButtonStyle $customStyle = null;

    public bool $carActions = false;

    public bool $actionButton = false;

    public bool $nukiButton = false;

    public bool $allow1min = false;

    public bool $customAllow1min = false;

    public ?string $defaultName = null;

    public bool $nukiIsLock = false;
    public ?string $nukiId = null;

    public bool $pinEnabled = false;

    public function getButtonAttributes(): string
    {
        $dataAttributes = [];
        $buttonTypesWhichNeedsAttributes = [ButtonType::ENTRANCE, ButtonType::GATE, ButtonType::MULTI];
        if ($this->buttonObj instanceof ButtonEntity && in_array($this->buttonObj->getType(), $buttonTypesWhichNeedsAttributes)) {
            $camera1 = $this->buttonObj->getCamera1();
            $camera2 = $this->buttonObj->getCamera2();
            $camera3 = null;
            $camera4 = null;

            if ($this->buttonObj->getType() === ButtonType::MULTI) {
                $camera3 = $this->buttonObj->getCamera3();
                $camera4 = $this->buttonObj->getCamera4();
            }

            if ($camera1 instanceof Camera) {
                $dataAttributes['data-camera1'] =  $camera1->getNameId();
            }

            if ($camera2 instanceof Camera) {
                $dataAttributes['data-camera2'] = $camera2->getNameId();
            }

            if ($camera3 instanceof Camera) {
                $dataAttributes['data-camera3'] = $camera3->getNameId();
            }

            if ($camera4 instanceof Camera) {
                $dataAttributes['data-camera4'] = $camera4->getNameId();
            }
        }
        return implode(' ', array_map(
            fn ($key, $value) => $key . '=' . $value,
            array_keys($dataAttributes),
            $dataAttributes
        ));
    }

    public function getText(): string
    {
        if ($this->text !== null) {
            return $this->text;
        }

        return $this->name;
    }

    public function getFormSubmitButton(): bool
    {
        return $this->formSubmitButton == 'true';
    }

    public function getButtonHasCamera(): bool
    {
        return ($this->buttonObj instanceof ButtonEntity && $this->buttonObj->getCamera1() instanceof Camera);
    }

    public function getCarActions(): bool
    {
        return $this->carActions;
    }

    public function getButtonClasses(): string
    {
        $classes = 'btn btn-block w-100 fw-light border-0';

        if (!$this->formSubmitButton) {
            $classes .= ' btn-large';
        } else {
            $classes .= ' btn-lg';
        }

        if ($this->buttonObj instanceof ButtonEntity && $this->customStyle === null) {
            match ($this->buttonObj->getButtonStyle()) {
                ButtonStyle::BASIC => $classes .= ' btn-basic',
                ButtonStyle::BLUE => $classes .= ' btn-primary border-primary hover-bg-primary',
                ButtonStyle::RED => $classes .= ' btn-danger border-danger hover-bg-red',
            };
        } elseif ($this->getFormSubmitButton()) {
            $classes .= ' btn-primary';
        } elseif ($this->customStyle) {
            match ($this->customStyle) {
                ButtonStyle::BASIC => $classes .= ' btn-basic',
                ButtonStyle::BLUE => $classes .= ' btn-primary border-primary hover-bg-primary',
                ButtonStyle::RED => $classes .= ' btn-danger border-danger hover-bg-red',
            };
        }

        if (!$this->getCarActions() && !$this->getFormSubmitButton()) {
            $classes .= ' btn-left';
        }

        if (!$this->getFormSubmitButton() && $this->getCarActions()) {
            $classes .= ' car-enter-exit-btn';
        }

        if ($this->actionButton) {
            $classes .= ' btn-action';
        }

        if ($this->nukiButton) {
            $classes .= ' btn-nuki btn-basic';
        }
        return $classes;
    }
}

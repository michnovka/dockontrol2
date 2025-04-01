<?php

declare(strict_types=1);

namespace App\Controller\CP\Settings;

use App\Controller\CP\AbstractCPController;
use App\Entity\Config;
use App\Entity\Enum\ConfigGroup;
use App\Entity\Enum\ConfigName;
use App\Entity\User;
use App\Form\ConfigType;
use App\Helper\ConfigHelper;
use App\Helper\UserActionLogHelper;
use App\Repository\ConfigRepository;
use App\Security\Voter\ConfigVoter;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

#[Route('/settings')]
#[IsGranted('ROLE_SUPER_ADMIN')]
class ConfigController extends AbstractCPController
{
    public function __construct(
        private readonly ConfigRepository $configRepository,
        private readonly ConfigHelper $configHelper,
        private readonly UserActionLogHelper $userActionLogHelper,
    ) {
    }

    #[Route('/config-options', name: 'cp_settings_config_option')]
    public function index(Request $request): Response
    {
        $savedConfigs = $this->configRepository->getAllIndexedByKey();
        $allConfigs = [];
        $groupedConfigs = [ConfigGroup::GENERAL->value => [], ConfigGroup::LOGS->value => []];
        $groupedForms = [ConfigGroup::GENERAL->value => [], ConfigGroup::LOGS->value => []];
        /** @var User $adminUser*/
        $adminUser = $this->getUser();

        foreach (ConfigName::cases() as $config) {
            $group = $config->getConfigGroup()->value;

            if (!isset($groupedConfigs[$group])) {
                continue;
            }

            $groupedConfigs[$group][] = $config;

            if ($config->isReadOnly()) {
                continue;
            }

            $configValue = isset($savedConfigs[$config->value])
                ? $this->configHelper->getConfigValue($savedConfigs[$config->value])
                : $config->getDefault();

            $allConfigs[$config->value] = [
                'value'   => $configValue,
                'default' => !isset($savedConfigs[$config->value]),
            ];

            $configForm = $this->createForm(ConfigType::class, null, [
                'config_name' => $config,
                'value' => $configValue,
                'required' => !($config === ConfigName::DOCKONTROL_NODE_ISSUE_ADMIN_NOTIFIED),
            ])->handleRequest($request);

            if ($configForm->isSubmitted() && $configForm->isValid()) {
                try {
                    $value = $configForm->get('config_' . $config->value)->getData();
                    $this->configHelper->setConfig($config, $value);
                    $this->userActionLogHelper->addUserActionLog('Updated config ' . $config->getReadable(), $adminUser);
                    $this->addFlash('success', 'Config updated successfully.');
                } catch (Throwable $throwable) {
                    $this->addFlash('danger', 'Failed to update config, ' . $throwable->getMessage());
                }

                return $this->redirectToRoute('cp_settings_config_option');
            }

            $groupedForms[$group][$config->value] = $configForm->createView();
        }

        return $this->render('cp/settings/config_options/index.html.twig', [
            'generalConfigs' => $groupedConfigs['general'],
            'logsConfigs' => $groupedConfigs['logs'],
            'allConfigs' => $allConfigs,
            'generalConfigForms' => $groupedForms['general'],
            'logConfigForms' => $groupedForms['logs'],
        ]);
    }


    #[Route('/config/{key}/delete', name: 'cp_settings_config_option_delete')]
    #[IsGranted(ConfigVoter::DELETE, 'config')]
    public function deleteConfig(
        Request $request,
        #[MapEntity(id: 'key')] Config $config,
    ): JsonResponse {
        $csrf = $request->request->getString('_csrf');
        $success = false;
        $errorMessage = null;
        /** @var User $adminUser*/
        $adminUser = $this->getUser();

        if (!$this->isCsrfTokenValid('settingcsrf', $csrf)) {
            $errorMessage = 'Invalid CSRF token';
        } else {
            try {
                $this->userActionLogHelper->addUserActionLog('Deleted config ' . $config->getConfigKey()->getReadable(), $adminUser);
                $this->configHelper->removeConfig($config);
                $this->addFlash('success', 'Config reset successfully.');
                $success = true;
            } catch (Throwable $throwable) {
                $errorMessage = 'Failed to delete config ' . $throwable->getMessage();
            }
        }

        return $this->json(['success' => $success, 'errorMessage' => $errorMessage]);
    }
}

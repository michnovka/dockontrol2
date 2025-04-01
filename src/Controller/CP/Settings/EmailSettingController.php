<?php

declare(strict_types=1);

namespace App\Controller\CP\Settings;

use App\Controller\CP\AbstractCPController;
use App\Entity\Enum\ConfigGroup;
use App\Entity\Enum\ConfigName;
use App\Entity\Enum\ConfigType as ConfigTypeEnum;
use App\Entity\User;
use App\Form\ConfigType;
use App\Helper\ConfigHelper;
use App\Helper\MailerHelper;
use App\Helper\UserActionLogHelper;
use App\Repository\ConfigRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

#[Route('/setting/email-setting')]
#[IsGranted('ROLE_SUPER_ADMIN')]
class EmailSettingController extends AbstractCPController
{
    public function __construct(
        private readonly MailerHelper $mailerHelper,
        private readonly ConfigHelper $configHelper,
        private readonly ConfigRepository $configRepository,
        private readonly UserActionLogHelper $userActionLogHelper,
    ) {
    }

    #[Route('/', name: 'cp_settings_email_setting')]
    public function index(Request $request): Response
    {
        /** @var User $adminUser */
        $adminUser = $this->getUser();

        $emailConfigNames = array_filter(ConfigName::cases(), function ($configName) {
            return $configName->getConfigGroup() === ConfigGroup::EMAIL;
        });

        $savedConfigs = $this->configRepository->getMultipleConfigValuesIndexedByKey($emailConfigNames);
        $allConfigs = [];
        $forms = [];

        /** @var ConfigName $config*/
        foreach ($emailConfigNames as $config) {
            if (!$config->isReadOnly()) {
                $configValue = isset($savedConfigs[$config->value]) ?
                    $this->configHelper->getConfigValue($savedConfigs[$config->value])
                    : $config->getDefault();

                $allConfigs[$config->value] = [
                    'value'   => $configValue,
                    'default' => !isset($savedConfigs[$config->value]),
                ];

                $configForm = $this->createForm(ConfigType::class, null, [
                    'config_name' => $config,
                    'value' => $configValue,
                    'required' => !($config->getConfigType() === ConfigTypeEnum::BOOLEAN),
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
                    return $this->redirectToRoute('cp_settings_email_setting');
                }

                $forms[$config->value] = $configForm->createView();
            }
        }

        return $this->render('cp/settings/email_setting/index.html.twig', [
            'emailConfigs' => $emailConfigNames,
            'forms' => $forms,
            'allConfigs' => $allConfigs,
        ]);
    }

    #[Route('/send-test-email', name: 'cp_settings_email_setting_send_test_email')]
    public function sendTestEmail(Request $request): Response
    {
        $email = $request->request->getString('email');
        $csrfToken = $request->request->getString('_csrf');
        $status = false;

        if ($this->isCsrfTokenValid('emailsetting', $csrfToken)) {
            try {
                $this->mailerHelper->sendEmail($email, 'TEST', 'E-mail set up correctly.');
                $this->addFlash('success', 'Test e-mail sent successfully.');
                $status = true;
            } catch (Throwable $throwable) {
                $this->addFlash('danger', 'Failed to send test e-mail.' . $throwable->getMessage());
            }
        } else {
            $this->addFlash('danger', 'Failed to send test e-mail. Invalid CSRF token.');
        }

        return $this->json(['status' => $status]);
    }
}

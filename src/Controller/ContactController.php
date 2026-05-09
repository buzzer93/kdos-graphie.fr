<?php

declare(strict_types=1);

namespace App\Controller;

use App\Form\ContactType;
use App\Repository\SiteSettingRepository;
use App\Service\ContactMailer;
use App\Service\RecaptchaService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        ContactMailer $contactMailer,
        RecaptchaService $recaptchaService,
        SiteSettingRepository $settingRepository,
        #[Autowire('%env(string:RECAPTCHA_SITE_KEY)%')]
        string $recaptchaSiteKey,
    ): Response {
        $form = $this->createForm(ContactType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $token = (string) $request->request->get('recaptcha_token', '');
            $remoteIp = (string) $request->getClientIp();

            if (!$recaptchaService->isValid($token, $remoteIp)) {
                $this->addFlash('danger', 'Verification anti-robot echouee. Veuillez reessayer.');

                return $this->render('contact/index.html.twig', [
                    'form' => $form,
                    'recaptchaSiteKey' => $recaptchaSiteKey,
                    'contactInfo' => $this->getContactInfo($settingRepository),
                ]);
            }

            /** @var array{name: string, email: string, subject: string, message: string} $data */
            $data = $form->getData();
            $contactMailer->sendContactMessage($data);

            $this->addFlash('success', 'Votre message a ete envoye. Nous vous repondrons dans les meilleurs delais.');

            return $this->redirectToRoute('app_contact');
        }

        return $this->render('contact/index.html.twig', [
            'form' => $form,
            'recaptchaSiteKey' => $recaptchaSiteKey,
            'contactInfo' => $this->getContactInfo($settingRepository),
        ]);
    }

    /**
     * @return array{phone: string, email: string, address: string, instagram: string, facebook: string}
     */
    private function getContactInfo(SiteSettingRepository $repo): array
    {
        return [
            'phone'     => $repo->getValue('contact_phone'),
            'email'     => $repo->getValue('contact_email'),
            'address'   => $repo->getValue('contact_address'),
            'instagram' => $repo->getValue('contact_instagram'),
            'facebook'  => $repo->getValue('contact_facebook'),
        ];
    }
}

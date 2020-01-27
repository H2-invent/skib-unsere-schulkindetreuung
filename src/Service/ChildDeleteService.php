<?php

namespace App\Service;

use App\Entity\Active;
use App\Entity\Kind;
use App\Entity\Organisation;
use App\Entity\Stadt;

use App\Entity\Stammdaten;
use Beelab\Recaptcha2Bundle\Form\Type\RecaptchaType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;


// <- Add this

class ChildDeleteService
{
    private $em;
    private $translator;
    private $templating;
    private $mailer;

    public function __construct(MailerService $mailer, Environment $environment, TranslatorInterface $translator, EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
        $this->translator = $translator;
        $this->templating = $environment;
        $this->mailer = $mailer;
    }

    public function deleteChild(Kind $kind)
    {
        try {
            $parent = $kind->getEltern();
            $parent->removeKind($kind);
            $this->em->persist($parent);
            $this->em->remove($kind);
            $this->em->flush();
            $this->sendEmail($parent, $kind, $kind->getSchule()->getOrganisation());
            return true;
        } catch (\Exception $exception) {
            return false;
        }

    }

    public function sendEmail(Stammdaten $stammdaten, Kind $kind, Organisation $organisation)
    {
        $mailBetreff = $this->translator->trans('Abmeldung der Schulkindbetreuung für ') . $kind->getVorname() . ' ' . $kind->getNachname();
        $mailContent = $this->templating->render('email/abmeldebestatigung.html.twig', array('eltern' => $stammdaten, 'kind' => $kind, 'org' => $organisation));
        $this->mailer->sendEmail($kind->getSchule()->getOrganisation()->getName(), $kind->getSchule()->getOrganisation()->getEmail(), $stammdaten->getEmail(), $mailBetreff, $mailContent);

    }
}

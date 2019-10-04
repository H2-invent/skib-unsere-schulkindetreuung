<?php

namespace App\Controller;

use App\Entity\Kind;
use App\Entity\Stammdaten;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class ChildChangeController extends AbstractController
{
    /**
     * @Route("/org_child/change/seccode", name="child_change_seccode")
     */
    public function change_seccodeAction(Request $request, TranslatorInterface $translator)
    {


        $kind = $this->getDoctrine()->getRepository(Kind::class)->find($request->get('kind_id'));
        $adresse = new Stammdaten();
        $adresse = $kind->getEltern();


        $input = array('seccode'=>'');

        $form = $this->createFormBuilder($input)
            ->add('seccode', TextType::class, ['label' => 'Sicherheitscode der Eltern', 'translation_domain' => 'form'])
           ->add('submit', SubmitType::class, ['attr'=> array('class'=> 'btn btn-outline-primary'), 'label' => 'weiter', 'translation_domain' => 'form'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            dump('test');
            $input = $form->getData();
            dump($input);
            if ($input['seccode'] == $adresse->getSecCode()){

                $cookie = new Cookie ('KindID', $kind->getId() . "." . hash("sha256", $kind->getId() . $this->getParameter("secret")));
                $cookie2 = new Cookie ('UserID', $adresse->getUid() . "." . hash("sha256", $adresse->getUid() . $this->getParameter("secret")));
                $cookie_seccode = new Cookie ('SecID', $adresse->getSecCode() . "." . hash("sha256", $adresse->getSecCode() . $this->getParameter("secret")));

                $response = $this->redirectToRoute('workflow_start',array('slug'=>$kind->getSchule()->getStadt()->getSlug()));
                $response->headers->setCookie($cookie);
                $response->headers->setCookie($cookie2);
                $response->headers->setCookie($cookie_seccode);
                return $response;
            }else{
                $text = $translator->trans('Sicherheitscode ungültig');
                return $this->redirectToRoute('child_change_seccode',array('kind_id'=>$kind->getId(),'snack'=>$text));

            }
        }

        return $this->render('child_change/seccode.html.twig', array('form' => $form->createView()));
    }
}

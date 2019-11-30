<?php

namespace App\Controller;

use App\Entity\Ferienblock;
use App\Entity\Kind;
use App\Entity\KindFerienblock;
use App\Entity\Organisation;
use App\Entity\Stadt;
use App\Entity\Stammdaten;
use App\Form\Type\LoerrachEltern;
use App\Form\Type\LoerrachKind;
use App\Service\StamdatenFromCookie;
use App\Service\ToogleKindFerienblock;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class FerienController extends AbstractController
{
    /**
     * @Route("/{slug}/ferien/adresse",name="ferien_adresse",methods={"GET","POST"})
     */
    public function adresseAction(Request $request, ValidatorInterface $validator, $slug)
    {

        $stadt = $this->getDoctrine()->getRepository(Stadt::class)->findOneBy(array('slug' => $slug));

        if ($stadt === null) {
            return $this->redirectToRoute('workflow_city_not_found');
        }
        // load parent address data into controller as $adresse
        $adresse = new Stammdaten;
        if ($this->getStammdatenFromCookie($request)) {
            $adresse = $this->getStammdatenFromCookie($request);
        } else {
            return $this->redirectToRoute('loerrach_workflow_adresse');
        }

        //Add SecCode into if to create a SecCode the first time to be not "null"
        if ($adresse->getUid() === null) {
            $adresse->setUid(md5(uniqid()))
                ->setAngemeldet(false);
            $adresse->setCreatedAt(new \DateTime());
        }

        //Check if admin has enabled ferienprogramm for the city
        if ($stadt->getFerienprogramm() === false) {
            return $this->redirect($this->generateUrl('workflow_start', array('slug' => $stadt->getSlug())));
        }

        $form = $this->createForm(LoerrachEltern::class, $adresse);
        $form->remove('alleinerziehend', 'kinderImKiga', 'beruflicheSituation', 'einkommen');
        $form->handleRequest($request);

        $errors = array();
        if ($form->isSubmitted() && $form->isValid()) {
            $adresse = $form->getData();
            $errors = $validator->validate($adresse);
            if (count($errors) == 0) {
                $adresse->setFin(false);
                $cookie = new Cookie ('UserID', $adresse->getUid() . "." . hash("sha256", $adresse->getUid() . $this->getParameter("secret")), time() + 60 * 60 * 24 * 365);
                $em = $this->getDoctrine()->getManager();
                $em->persist($adresse);
                $em->flush();
                $response = $this->redirectToRoute('workflow_confirm_Email', array('redirect' => $this->generateUrl('ferien_auswahl', array('slug' => $stadt->getSlug())), 'uid' => $adresse->getUid(), 'stadt' => $stadt->getId()));
                $response->headers->setCookie($cookie);
                return $response;
            }

        }

        return $this->render('ferien/adresse.html.twig', array('stadt' => $stadt, 'form' => $form->createView(), 'errors' => $errors));
    }


    /**
     * @Route("/{slug}/ferien/auswahl", name="ferien_auswahl", methods={"GET"})
     * @ParamConverter("stadt", options={"mapping"={"slug"="slug"}})
     */
    public function ferienAction(Request $request, Stadt $stadt, StamdatenFromCookie $stamdatenFromCookie)
    {
        // Load all schools from the city into the controller as $schulen
        $org = $this->getDoctrine()->getRepository(Organisation::class)->findBy(array('stadt' => $stadt, 'deleted' => false));

        //Include Parents in this route
        if ($stamdatenFromCookie->getStammdatenFromCookie($request)) {
            $adresse = $stamdatenFromCookie->getStammdatenFromCookie($request);
        }

        $kinder = array();
        if ($request->cookies->get('KindID')) {
            $cookie_kind = explode('.', $request->cookies->get('KindID'));
            $kinder = $this->getDoctrine()->getRepository(Kind::class)->findBy(array('id' => $cookie_kind[0]));
        } else {
            $kinder = $adresse->getKinds()->toArray();
        }

        return $this->render('ferien/ferien.html.twig', array('org' => $org, 'stadt' => $stadt, 'adresse' => $adresse, 'kinder' => $kinder));

    }


    /**
     * @Route("/{slug}/ferien/kind/neu",name="ferien_kind_neu",methods={"GET","POST"})
     * @ParamConverter("stadt", options={"mapping"={"slug"="slug"}})
     */
    public function ferienNeukindAction(Request $request, ValidatorInterface $validator, TranslatorInterface $translator, Stadt $stadt, StamdatenFromCookie $stamdatenFromCookie)
    {
        //Include Parents in this route
        if ($stamdatenFromCookie->getStammdatenFromCookie($request)) {
            $adresse = $stamdatenFromCookie->getStammdatenFromCookie($request);
        }

        $kind = new Kind();
        $kind->setEltern($adresse);
        $kind->setSchule(null);
        $form = $this->createForm(LoerrachKind::class, $kind, array('action' => $this->generateUrl('ferien_kind_neu', array('slug' => $stadt->getSlug()))));
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $kind = $form->getData();
            $errors = $validator->validate($kind);
            try {
                if (count($errors) === 0) {
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($kind);
                    $em->flush();
                    $text = $translator->trans('Erfolgreich gespeichert');
                    return new JsonResponse(array('error' => 0, 'snack' => $text, 'next' => $this->generateUrl('ferien_kind_programm', array('slug' => $stadt->getSlug(), 'kind_id' => $kind->getId()))));
                }
            } catch (\Exception $e) {
                $text = $translator->trans('Fehler. Bitte versuchen Sie es erneut.');
                return new JsonResponse(array('error' => 1, 'snack' => $text));
            }
        }
        return $this->render('ferien/kindForm.html.twig', array('stadt' => $stadt, 'form' => $form->createView()));
    }


    /**
     * @Route("/{slug}/ferien/programm",name="ferien_kind_programm",methods={"GET","POST"})
     * @ParamConverter("stadt", options={"mapping"={"slug"="slug"}})
     */
    public function programmAction(Request $request, ValidatorInterface $validator, TranslatorInterface $translator, Stadt $stadt, StamdatenFromCookie $stamdatenFromCookie)
    {

        //Include Parents in this route
        if ($stamdatenFromCookie->getStammdatenFromCookie($request)) {
            $adresse = $stamdatenFromCookie->getStammdatenFromCookie($request);
        }

        $kind = $this->getDoctrine()->getRepository(Kind::class)->findOneBy(array('eltern' => $adresse, 'id' => $request->get('kind_id')));
        $dates = $this->getDoctrine()->getRepository(Ferienblock::class)->findFerienblocksFromToday($stadt);
        $today = new \DateTime('today');

        return $this->render('ferien/blocks.html.twig', array('kind' => $kind, 'dates' => $dates, 'stadt' => $stadt, 'today' => $today));
    }


    /**
     * @Route("/{slug}/ferien/programm/toggle",name="ferien_kinder_block_toggle",methods={"PATCH"})
     * @ParamConverter("stadt", options={"mapping"={"slug"="slug"}})
     */
    public function ferienblocktoggleAction(Request $request, ValidatorInterface $validator, TranslatorInterface $translator, ToogleKindFerienblock $toogleKindFerienblock)
    {

        $adresse = new Stammdaten;
        if ($this->getStammdatenFromCookie($request)) {
            $adresse = $this->getStammdatenFromCookie($request);
        }

        $kind = $this->getDoctrine()->getRepository(Kind::class)->findOneBy(array('eltern' => $adresse, 'id' => $request->get('kind_id')));
        $block = $this->getDoctrine()->getRepository(Ferienblock::class)->find($request->get('block_id'));
        $result = $toogleKindFerienblock->toggleKind($kind, $block, $request->get('preis_id'));

        return new JsonResponse($result);
    }


    /**
     * @Route("/{slug}/ferien/bezahlung",name="ferien_bezahlung",methods={"Get","POST"})
     * @ParamConverter("stadt", options={"mapping"={"slug"="slug"}})
     */
    public function paymentAction(Request $request, ValidatorInterface $validator, TranslatorInterface $translator, Stadt $stadt, StamdatenFromCookie $stamdatenFromCookie)
    {
        //Include Parents in this route
        if ($stamdatenFromCookie->getStammdatenFromCookie($request)) {
            $adresse = $stamdatenFromCookie->getStammdatenFromCookie($request);
        }

        return $this->render('ferien/bezahlung.html.twig', array('stadt' => $stadt));
    }


    /**
     * @Route("/{slug}/ferien/zusammenfassung",name="ferien_zusammenfassung",methods={"Get","POST"})
     * @ParamConverter("stadt", options={"mapping"={"slug"="slug"}})
     */
    public function zusammenfassungAction(Request $request, ValidatorInterface $validator, TranslatorInterface $translator, Stadt $stadt, StamdatenFromCookie $stamdatenFromCookie)
    {
        try {
            //Include Parents in this route
            if ($stamdatenFromCookie->getStammdatenFromCookie($request)) {
                $adresse = $stamdatenFromCookie->getStammdatenFromCookie($request);
            }

            $kind = $adresse->getKinds();
            $org = $this->getDoctrine()->getRepository(KindFerienblock::class)->findBy(array('kind' => $kind));


            dump($org);
        } catch (\Exception $e) {
            $result['text'] = $translator->trans('Fehler. Bitte versuchen Sie es erneut.');
        }

        return $this->render('ferien/zusammenfassung.html.twig', array('kind' => $kind, 'eltern' => $adresse, 'stadt' => $stadt, 'error' => true));
    }


    // Nach UId und Fin fragen
    private function getStammdatenFromCookie(Request $request)
    {
        if ($request->cookies->get('UserID')) {


            $cookie_ar = explode('.', $request->cookies->get('UserID'));
            $hash = hash("sha256", $cookie_ar[0] . $this->getParameter("secret"));
            $search = array('uid' => $cookie_ar[0], 'saved' => false);
            if ($request->cookies->get('KindID') && $request->cookies->get('SecID')) {


                $cookie_kind = explode('.', $request->cookies->get('KindID'));

                $cookie_seccode = explode('.', $request->cookies->get('SecID'));


            } else {
                $search['history'] = 0;
            }

            if ($hash == $cookie_ar[1]) {
                $adresse = $this->getDoctrine()->getRepository(Stammdaten::class)->findOneBy($search);
                return $adresse;
            }

            return null;
        }
        return null;
    }


}

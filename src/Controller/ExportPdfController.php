<?php

namespace App\Controller;

use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Sortie;
use Doctrine\ORM\EntityManagerInterface;

class ExportPdfController extends AbstractController
{
    #[Route('/sortie/{id}/pdf', name: 'app_sortie_pdf')]
    public function exportSortiePdf(Sortie $sortie, EntityManagerInterface $em): Response
    {
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        $pdfOptions->set('isRemoteEnabled', true); // nécessaire pour les images externes

        $dompdf = new Dompdf($pdfOptions);

        $html = $this->renderView('sortie/pdf.html.twig', [
            'sortie' => $sortie,
            'noteMoyenne' => null, // à adapter selon ton calcul
        ]);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'sortie_' . $sortie->getId() . '.pdf';

        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => sprintf('attachment; filename="%s"', $filename)
            ]
        );
    }
}

<?php

namespace App\Controller;

use App\Entity\Payment;
use App\Entity\Beat;
use App\Entity\Cart;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Security\Core\Security;
use Psr\Log\LoggerInterface;

class PaymentController extends AbstractController
{
    private $security;
    private $logger;

    public function __construct(Security $security, LoggerInterface $logger)
    {
        $this->security = $security;
        $this->logger = $logger;
    }

    /**
     * @Route("/payment/checkout/cart/{id}", name="payment_checkout_cart")
     */
    public function checkoutCart(Cart $cart, EntityManagerInterface $entityManager): Response
    {
        // Check if user is logged in
        if (!$this->security->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('app_login');
        }

        // Check if cart belongs to user
        if ($cart->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('This cart does not belong to you.');
        }

        // Log the cart checkout
        $this->logger->info('Cart checkout initiated', [
            'cart_id' => $cart->getId(),
            'user_id' => $this->getUser()->getId(),
            'total_amount' => $cart->getTotal()
        ]);

        return $this->render('payment/checkout.html.twig', [
            'cart' => $cart,
            'total' => $cart->getTotal()
        ]);
    }

    /**
     * @Route("/payment/process/cart/{id}", name="payment_process_cart", methods={"POST"})
     */
    public function processCart(Cart $cart, Request $request, EntityManagerInterface $entityManager): Response
    {
        // Check if user is logged in
        if (!$this->security->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('app_login');
        }

        // Check if cart belongs to user
        if ($cart->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('This cart does not belong to you.');
        }

        // Log the payment process
        $this->logger->info('Processing payment for cart', [
            'cart_id' => $cart->getId(),
            'user_id' => $this->getUser()->getId(),
            'total_amount' => $cart->getTotal()
        ]);

        $payments = [];
        $transactionId = uniqid('TRX');

        // Create a payment for each beat in the cart
        foreach ($cart->getCartItems() as $item) {
            $payment = new Payment();
            $payment->setUser($this->getUser());
            $payment->setBeat($item->getBeat());
            $payment->setAmount($item->getPrice());
            $payment->setTransactionId($transactionId);
            $payment->setStatus('completed');
            
            $entityManager->persist($payment);
            $payments[] = $payment;
        }

        // Set cart status to completed and clear items
        foreach ($cart->getCartItems() as $item) {
            $entityManager->remove($item);
        }
        $cart->setStatus('completed');
        $cart->setTotal(0);

        $entityManager->flush();

        // Redirect to success page with the first payment ID
        return $this->redirectToRoute('payment_success_cart', [
            'transactionId' => $transactionId
        ]);
    }

    /**
     * @Route("/payment/success/cart/{transactionId}", name="payment_success_cart")
     */
    public function successCart(string $transactionId, EntityManagerInterface $entityManager): Response
    {
        // Check if user is logged in
        if (!$this->security->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('app_login');
        }

        // Get all payments with this transaction ID
        $payments = $entityManager->getRepository(Payment::class)->findBy([
            'transactionId' => $transactionId,
            'user' => $this->getUser()
        ]);

        if (empty($payments)) {
            throw $this->createNotFoundException('Payments not found.');
        }

        // Log successful payment
        $this->logger->info('Cart payment successful', [
            'transaction_id' => $transactionId,
            'user_id' => $this->getUser()->getId(),
            'payment_count' => count($payments)
        ]);

        return $this->render('payment/success.html.twig', [
            'payments' => $payments,
            'transactionId' => $transactionId,
            'total' => array_sum(array_map(fn($p) => $p->getAmount(), $payments))
        ]);
    }

    /**
     * @Route("/payment/checkout/{id}", name="payment_checkout")
     */
    public function checkout(Beat $beat, EntityManagerInterface $entityManager): Response
    {
        // Check if user is logged in
        if (!$this->security->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('app_login');
        }

        // Log the beat information
        $this->logger->info('Checkout initiated for beat', [
            'beat_id' => $beat->getId(),
            'beat_title' => $beat->getTitle(),
            'user_id' => $this->getUser()->getId()
        ]);

        // Check if beat exists
        if (!$beat) {
            throw $this->createNotFoundException('Beat not found');
        }

        return $this->render('payment/checkout.html.twig', [
            'beat' => $beat,
        ]);
    }

    /**
     * @Route("/payment/process/{id}", name="payment_process", methods={"POST"})
     */
    public function process(Beat $beat, Request $request, EntityManagerInterface $entityManager): Response
    {
        // Check if user is logged in
        if (!$this->security->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('app_login');
        }

        // Log the payment process
        $this->logger->info('Processing payment for beat', [
            'beat_id' => $beat->getId(),
            'user_id' => $this->getUser()->getId()
        ]);

        // Create new payment
        $payment = new Payment();
        $payment->setUser($this->getUser());
        $payment->setBeat($beat);
        $payment->setAmount($beat->getPrice());
        $payment->setTransactionId(uniqid('TRX'));
        $payment->setStatus('completed');

        $entityManager->persist($payment);

        // Clear the user's cart
        $cart = $entityManager->getRepository(Cart::class)->findOneBy([
            'user' => $this->getUser(),
            'status' => 'pending'
        ]);

        if ($cart) {
            // Remove all cart items
            foreach ($cart->getCartItems() as $item) {
                $entityManager->remove($item);
            }
            // Set cart status to completed
            $cart->setStatus('completed');
            $cart->setTotal(0);
        }

        $entityManager->flush();

        return $this->redirectToRoute('payment_success', ['id' => $payment->getId()]);
    }

    /**
     * @Route("/payment/success/{id}", name="payment_success")
     */
    public function success(Payment $payment): Response
    {
        // Check if user is logged in and owns the payment
        if (!$this->security->isGranted('IS_AUTHENTICATED_FULLY') || $payment->getUser() !== $this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        // Log successful payment
        $this->logger->info('Payment successful', [
            'payment_id' => $payment->getId(),
            'beat_id' => $payment->getBeat()->getId(),
            'user_id' => $this->getUser()->getId()
        ]);

        return $this->render('payment/success.html.twig', [
            'payment' => $payment,
        ]);
    }

    /**
     * @Route("/payment/download/{id}", name="payment_download")
     */
    public function download(Payment $payment): Response
    {
        // Check if user is logged in and owns the payment
        if (!$this->security->isGranted('IS_AUTHENTICATED_FULLY') || $payment->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You do not have permission to download this beat.');
        }

        // Check if payment status is completed
        if ($payment->getStatus() !== 'completed') {
            throw $this->createAccessDeniedException('Payment must be completed before downloading.');
        }

        $beat = $payment->getBeat();
        $filePath = $this->getParameter('beats_directory') . '/' . $beat->getAudioFile();

        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('Beat file not found.');
        }

        // Log download attempt
        $this->logger->info('Beat download initiated', [
            'payment_id' => $payment->getId(),
            'beat_id' => $beat->getId(),
            'user_id' => $this->getUser()->getId()
        ]);

        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $beat->getTitle() . '.mp3'
        );

        return $response;
    }

    /**
     * @Route("/payment/history", name="payment_history")
     */
    public function history(EntityManagerInterface $entityManager): Response
    {
        if (!$this->security->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('app_login');
        }

        $payments = $entityManager->getRepository(Payment::class)
            ->findBy(['user' => $this->getUser()], ['createdAt' => 'DESC']);

        return $this->render('payment/history.html.twig', [
            'payments' => $payments,
        ]);
    }
} 
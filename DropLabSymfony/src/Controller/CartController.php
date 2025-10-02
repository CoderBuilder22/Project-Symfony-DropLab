<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Beat;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/cart')]
class CartController extends AbstractController
{
    #[Route('', name: 'app_cart_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $cart = $entityManager->getRepository(Cart::class)->findOneBy([
            'user' => $user,
            'status' => 'pending'
        ]);

        return $this->render('cart/index.html.twig', [
            'cart' => $cart,
        ]);
    }

    #[Route('/add/{id}', name: 'app_cart_add', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function add(Beat $beat, Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        
        // Find or create cart
        $cart = $entityManager->getRepository(Cart::class)->findOneBy([
            'user' => $user,
            'status' => 'pending'
        ]);

        if (!$cart) {
            $cart = new Cart();
            $cart->setUser($user);
            $entityManager->persist($cart);
        }

        // Check if beat already in cart
        $cartItem = $entityManager->getRepository(CartItem::class)->findOneBy([
            'cart' => $cart,
            'beat' => $beat
        ]);

        if (!$cartItem) {
            $cartItem = new CartItem();
            $cartItem->setCart($cart);
            $cartItem->setBeat($beat);
            $cartItem->setPrice($beat->getPrice());
            $entityManager->persist($cartItem);
        } else {
            $cartItem->setQuantity($cartItem->getQuantity() + 1);
        }

        // Update cart total
        $cart->setTotal($cart->getTotal() + $beat->getPrice());

        $entityManager->flush();

        $this->addFlash('success', 'Beat added to cart successfully!');
        
        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/remove/{id}', name: 'app_cart_remove', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function remove(CartItem $cartItem, EntityManagerInterface $entityManager): Response
    {
        $cart = $cartItem->getCart();
        
        if ($cart->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        // Update cart total
        $cart->setTotal($cart->getTotal() - ($cartItem->getPrice() * $cartItem->getQuantity()));
        
        $entityManager->remove($cartItem);
        $entityManager->flush();

        $this->addFlash('success', 'Item removed from cart successfully!');
        
        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/clear', name: 'app_cart_clear', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function clear(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $cart = $entityManager->getRepository(Cart::class)->findOneBy([
            'user' => $user,
            'status' => 'pending'
        ]);

        if ($cart) {
            foreach ($cart->getCartItems() as $item) {
                $entityManager->remove($item);
            }
            $cart->setTotal(0);
            $entityManager->flush();
        }

        $this->addFlash('success', 'Cart cleared successfully!');
        
        return $this->redirectToRoute('app_cart_index');
    }
} 
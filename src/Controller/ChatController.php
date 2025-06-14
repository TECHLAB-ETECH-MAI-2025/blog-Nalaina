<?php

namespace App\Controller;

use App\Entity\Message;
use App\Entity\User;
use App\Form\MessageForm;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;

#[Route('/chat', name: 'chat_')]
class ChatController extends AbstractController
{

    #[Route('/send', name: 'send', methods: ['POST'])]
    public function sendMessage(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response
    {
        $content = $request->request->get('content');
        $receiverId = $request->request->get('receiver');

        $message = new Message();
        $message->setSender($this->getUser());
        $message->setReceiver($entityManager->getRepository(User::class)->find($receiverId));
        $message->setContent($content);
        $message->setCreatedAt(new \DateTimeImmutable());

        $entityManager->persist($message);
        $entityManager->flush();

        return new JsonResponse(['success' => true, 'message' => 'Message envoyé avec succès.']);
        
    }

    
    #[Route('/messages', name: 'chat_messages', methods: ['GET'])]
    public function messages(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response
    {
        /** @var \App\Entity\User $currentUser */
        $currentUser = $this->getUser();
        $receiverId = $request->query->get('receiver');

        if (!$receiverId) {
            return new JsonResponse(['error' => 'Aucun destinataire.'], Response::HTTP_BAD_REQUEST);
        }

        $receiver = $entityManager->getRepository(User::class)->find($receiverId);
        if (!$receiver) {
            return new JsonResponse(['error' => 'Utilisateur non trouvé.'], Response::HTTP_NOT_FOUND);
        }

        $messages = $entityManager->getRepository(Message::class)
            ->findConversation($currentUser->getId(), $receiver->getId());

        return $this->render('chat/messages.html.twig', [
            'messages' => $messages,
            'receiver' => $receiver,
        ]);
    }

    #[Route('/{receiverId}', name: 'with_user')]
    public function index(
        int $receiverId,
        MessageRepository $messageRepository,
        EntityManagerInterface $entityManager,
        Request $request
    ): Response
    {
        // if (!$receiverId) {
        //     return new Response('Aucun utilisateur sélectionné.', Response::HTTP_BAD_REQUEST);
        // }
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        if (!$currentUser instanceof UserInterface){
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }

        $receiver = $entityManager->getRepository(User::class)->find($receiverId);
        if (!$receiver) {
            throw $this->createNotFoundException('Utilisateur non trouvé.');
        }

        $messages = $messageRepository->findConversation($currentUser->getId(), $receiverId);

        $message = new Message();
        $form = $this->createForm(MessageForm::class, $message);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $message->setSender($currentUser);
            $message->setReceiver($receiver);
            $message->setCreatedAt(new \DateTimeImmutable());

            $entityManager->persist($message);
            $entityManager->flush();

            return $this->redirectToRoute('chat_index', ['receiverId' => $receiverId]);
        }
        return $this->render('chat/index.html.twig', [
            'messages' => $messages,
            'receiver' => $receiver,
            'form' => $form->createView(),
        ]);
    }

    #[Route('', name: 'index')]
    public function usersList(
        UserRepository $userRepository,
        MessageRepository $messageRepository
    ): Response
    {
        /** @var \App\Entity\User $currentUser */
        $currentUser = $this->getUser();
        $users = $userRepository->findAllExceptCurrentSortedByName($currentUser->getId());

        $messageCounts = [];
        foreach ($users as $user) {
            /** @var \App\Entity\User $user */
            $messageCounts[$user->getId()] = $messageRepository->countMessageWithUser($currentUser->getId(), $user->getId());

        }

        return $this->render('chat/user_list.html.twig', [
            'users' => $users,
            'messageCounts' => $messageCounts,
        ]);
    }
}

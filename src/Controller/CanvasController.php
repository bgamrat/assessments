<?php

namespace App\Controller;

use App\Security\CanvasLMSAuthenticator;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CanvasController extends AbstractController {

    private $httpClient;

    public function __construct(HttpClientInterface $httpClient) {
        $this->httpClient = $httpClient;
    }

    /**
     * Link to this controller to start the "connect" process
     *
     * @Route("/login")
     */
    public function connectAction(ClientRegistry $clientRegistry) {
        // will redirect to Canvas
        return $clientRegistry
                        ->getClient('canvas_lms') // key used in config/packages/knpu_oauth2_client.yaml
                        ->redirect([ '/login/oauth2/token' 
                           //'/auth/userinfo' // thanks to: https://community.canvaslms.com/t5/Canvas-Developers-Group/API-Login-with-email-password/m-p/468376/highlight/true#M7272
        ]);
    }

    /**
     * After going to Canvas, you're redirected back here
     * because this is the "redirect_route" you configured
     * in config/packages/knpu_oauth2_client.yaml
     *
     * @Route("/login/check", name="login_check")
     */
    public function connectCheckAction(Request $request, ClientRegistry $clientRegistry, CanvasLMSAuthenticator $canvasLMSAuthenticator) {
        $client = $clientRegistry->getClient('canvas_lms');
        $oauth2Provider = $client->getOAuth2Provider();
        $code = $request->get('code');
        $accessToken = $oauth2Provider->getAccessToken('authorization_code', [
            'code' => $code
        ]);
        $resourceOwner = $oauth2Provider->getResourceOwner($accessToken);
        dump($resourceOwner->getId());die;    }
    
    /**
     *
     * @Route("/ready")
     */
    
    public function readyAction(ClientRegistry $clientRegistry) {
        $client = $clientRegistry->getClient('canvas_lms');
        try {
            $user = $client->fetchUser();
            $token = $client->getAccessToken();
            $response = $this->httpClient->request(
                    'GET',
                    'https://canvas.nashuaweb.net/api/v1/users/self/profile',
                    [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $token,
                        ]
                    ],
            );
            $content = $response->getContent();

            $profile = json_decode($content);
            return $this->render('pages/ready.html.twig', ['code' => $code, 'profile' => $profile]);
            // ...
        } catch (IdentityProviderException $e) {
            // something went wrong!
            // probably you should return the reason to the user
            var_dump($e->getMessage());
            die;
        }
    }

}

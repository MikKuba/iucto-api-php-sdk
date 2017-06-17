<?php

namespace IUcto;

require_once __DIR__ . '/ConnectionException.php';

/**
 * Description of Connector
 *
 * @author admin
 */
class Connector {

    const GET = 'GET';
    const PUT = 'PUT';
    const POST = 'POST';
    const DELETE = 'DELETE';

    private $curl;
    private $apiKey;
    private $version;
    private $endpoint;

    public function __construct(Curl $curl, $apiKey, $version, $endpoint) {
        $this->curl = $curl;
        $this->apiKey = $apiKey;
        $this->version = $version;
        $this->endpoint = rtrim($endpoint, '/');
    }

    /**
     * Request the server
     * 
     * @param string $address
     * @param string $method
     * @param mixed[] $data
     * @return mixed[]
     * @throws Exception
     * @throws ConnectionException
     */
    public function request($address, $method, $data) {
        $response = null;
        $url = $this->endpoint . "/" . $this->version . "/" . $address;
        switch ($method) {
            case self::GET:
                $response = $this->curl->get($url, $data);
                break;
            case self::POST:
                $response = $this->curl->post($url, $data);
                break;
            case self::PUT:
                $response = $this->curl->put($url, $data);
                break;
            case self::DELETE:
                $response = $this->curl->delete($url, $data);
                break;
            default:
                throw new Exception("Unknown method type " . $method);
        }
        if ($this->curl->curl_error) {
            throw new ConnectionException(sprintf("Error while requesting endpoint. Original message: %s", $this->curl->error_message));
        }
        if ($this->curl->error_code > 205 && $this->curl->response === '') {
            $appended = "Operaci nelze provest. ";
            switch ($this->curl->error_code) {
                case 400:
                    $appended .= "Vraceny kod 400 muze znamenat tyto moznosti: Komunikace mus� prob�hat p�es protokol HTTPS.|Neplatn� verze API, nebo zdroj.|T�lo po�adavku je pr�zdn�.|Neplatn� JSON form�t.|Parametr 'doctype' je povinn�.|Parametr 'doctype' nen� platn�.|Parametr 'date' je povinn�.|Parametr 'date' nen� platn�.";
                    break;
                case 401:
                    $appended .= 'Zkontrolujte pros�m, zda je v� API kl�� uveden spr�vn�.';
                    break;
                case 403:
                    $appended .= 'Vraceny kod 403 muze znamenat tyto moznosti: Nelze smazat z�znam (m� na sob� dal�� z�vsilosti).| ��etn� obdob�, nebo obdob� DPH je uzav�eno.';
                    break;
                case 404:
                    $appended .= 'Z�znam nenalezen';
                    break;
                default:
                    $appended .= $this->curl->error_message;
            }

            throw new ConnectionException(sprintf("Error while connecting to %s. Returned code is %s. Body content: %s. Message: %s", $url, $this->curl->error_code, $this->curl->response, $appended), $this->curl->error_code);
        }

        return $response;
    }

}

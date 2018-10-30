<?PHP

namespace hiapi\directi;

use err;
use check;
use fix;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use hiapi\directi\exceptions\DirectiException;
use hiapi\directi\exceptions\ValidationException;

class HttpClient
{
    protected $client;

    /**
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function performRequest (
        string  $httpMethod,
        string  $command,
        array   $data,
        array   $inputs=null,
        array   $returns=null,
        array   $auxData=null
    ) {
        $data = $inputs ? check::values($inputs, $data) : $data;
        if (err::is($data)) {
            throw new ValidationException(err::get($data));
        }
        if ($auxData) {
            $data = array_merge($data, $auxData);
        }
        $guzzleResponse = $this->request($httpMethod, $command, $data);
        $response = $this->parseGuzzleResponse($guzzleResponse);
        $check = fix::values($returns, $response);
        if (err::is($check)) {
            throw new ValidationException(err::get($check));
        }
        return $response;
    }

    /**
     * @param array $data
     * @return string
     */
    private function prepareQuery(array $data): string
    {
        return preg_replace('/%5B[0-9]+%5D/simU', '', http_build_query($data));
    }

    private function request (string $httpMethod, string $command, array $data): ?Response
    {
        if (!strcasecmp($httpMethod, 'GET')) {
            return $this->fetchGet($command, $data);
        }
        else if (!strcasecmp($httpMethod, 'POST')) {
            return $this->fetchPost($command, $data);
        }
        return null;
    }

    /**
     * @param $guzzleResponse
     * @return array|int
     */
    private function parseGuzzleResponse($guzzleResponse)
    {
        $response = $guzzleResponse->getBody()->getContents();
        $response = json_decode($response, true);
        if (is_array($response) && array_key_exists('error', $response)) {
            throw new DirectiException($response['error']);
        }

        return $response;
    }

    /**
     * @param string $command
     * @param array $data
     * @return Response
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function fetchGet (string $command, array $data): Response
    {
        $query = $command . '?' . $this->prepareQuery($data);
        return $this->client->request('GET', $query);
    }

    /**
     * @param string $command
     * @param array|null $data
     * @return Response
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function fetchPost (string $command, array $data=null): Response
    {
        $query = $this->prepareQuery($data);
        $res = $this->client->request('POST',  $command, [
            'body' => $query,
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
        ]);
        return $res;
    }
};

<?PHP

namespace hiapi\directi;

use err;
use check;
use GuzzleHttp\Client;

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

    public function checkedRequest (
        string  $method='',
        string  $name,
        array   $data,
        array   $inputs=null,
        array   $returns=null,
        array   $add_data=null
    ) {
        $data = $inputs ? check::values($inputs,$data) : $data;
        if (err::is($data)) return $data;
        if ($add_data) $data = array_merge($data,$add_data);
        $res = $this->request($method,$name,$data);
        if (err::is($res)) return $res;
        return $returns ? fix::values($returns,$res) : $res;
    }

    public function request (string $method, string $name, array $data) {
        return err::setifnot($this->getJSON($method, $name, $data), 'unknown error');
    }

    public function getJSON (string $method,string $url,array $data=null) {
        return json_decode($this->getPlain($url, $data, $method), true);
    }

    public function getPlain (string $method='', string $url, array $data=null)
    {
        if (isset($data[''])) {
            $tmp = $data[''];
            unset($data['']);
            array_push($data,$tmp);
        }
        if (!$method) $method = $this->method ?: 'post';
        $fetchMethod = "fetch$method";

        return trim(static::$fetchMethod($url, $data));
    }

    static public function fetchGet (string $url, array $data=null)
    {

    }

    static public function fetchPost (string $url, array $data=null)
    {

    }
};

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use App\Models\Message;
use App\Models\User;
use App\Models\OauthAccessTokens;
use Auth;
use DB;
use Carbon\Carbon;

class WebSocketController implements MessageComponentInterface {

    protected $clients;
    private $subscriptions;
    private $users;
    private $userresources;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->subscriptions = [];
        $this->users = [];
        $this->userresources = [];
    }

    /**
     * [onOpen description]
     * @method onOpen
     * @param  ConnectionInterface $conn [description]
     * @return [JSON]                    [description]
     * @example connection               var conn = new WebSocket('ws://localhost:8090');
     */
    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        $this->users[$conn->resourceId] = $conn;
        $conn->send(json_encode('You have established the connection, please start chatting.'));
    }

    /**
     * [onMessage description]
     * @method onMessage
     * @param  ConnectionInterface $conn [description]
     * @param  [JSON.stringify]              $msg  [description]
     * @return [JSON]                    [description]
     * @example subscribe                conn.send(JSON.stringify({"command": "subscribe", channel: "global"}));
     * @example groupchat                conn.send(JSON.stringify({command: "groupchat", message: "hello glob", channel: "global"}));
     * @example message                  conn.send(JSON.stringify({"command":"message", "token":"vytcdytuvib6f55sdxr76tc7uvikg8f7", "to": "1", "from":"2", "message":"it needs xss protection"}));
     * @example register                 conn.send(JSON.stringify({"command": "register", "userId": "1", "token":"vytcdytuvib6f55sdxr76tc7uvikg8f7"}));
     */
    public function onMessage(ConnectionInterface $conn, $msg) {
        $data = json_decode($msg);
        if (isset($data->command)) {
            if (isset($data->token)) {
                $getToken = explode(',,+',$data->token);
                $token = @$getToken[0];
                $channel_id = @$getToken[1];
                $plateform_type = @$getToken[2];
                $userToken = $this->checkUserToken($token);

                if (!empty($userToken)) {
                    $checkToken = OauthAccessTokens::where([
                                ['id', '=', $userToken['user_token']],
                                ['expires_at', '>', Carbon::now()]
                            ])->first();
                    if (isset($checkToken) && !$checkToken) {
                        $conn->send(json_encode('Token time has expired. Please log in again'));
                    }
                } else {
                    $conn->send(json_encode('Token not valid'));
                }
                switch ($data->command) {
                    case "message":
                    

                        if ($userToken['user_id'] != $data->from) {
                            $conn->send(json_encode('user not matched'));
                        } else {
                            if (isset($this->userresources[$data->to])) {
                                foreach ($this->userresources[$data->to] as $key => $resourceId) {
                                    if (isset($this->users[$resourceId])) {
                                        $this->users[$resourceId]->send($msg);
                                    }
                                }
                                $conn->send(json_encode($this->userresources[$data->to]));
                            }
                            if (isset($this->userresources[$data->from])) {
                                foreach ($this->userresources[$data->from] as $key => $resourceId) {
                                    if (isset($this->users[$resourceId]) && $conn->resourceId != $resourceId) {
                                        $this->users[$resourceId]->send($msg);
                                    }
                                }
                            }
                            $conn->send(json_encode(['message' => 'Messege Sent','created_at' => date('Y-m-d H:i:s')]));

                            $message = new Message();
                            $message->sender_id = $data->from;
                            $message->receiver_id = $data->to;
                            $message->message = $data->message;
                            $message->channel_id = $channel_id;
                            $message->plateform_type = $plateform_type;
                            $message->is_read = '0';
                            $message->is_system_generated = '1';
                            $message->save();
                        }
                        break;
                    case "register":
                        //
                        if (isset($data->userId)) {
                            if ($userToken['user_id'] != $data->userId) {
                                $conn->send(json_encode('user not matched'));
                            } else {
                                if (isset($this->userresources[$data->userId])) {
                                    if (!in_array($conn->resourceId, $this->userresources[$data->userId])) {
                                        $this->userresources[$data->userId][] = $conn->resourceId;
                                    }
                                } else {
                                    $this->userresources[$data->userId] = [];
                                    $this->userresources[$data->userId][] = $conn->resourceId;
                                }
                            }
                        }
                        //$conn->send(json_encode($this->users));
                        //$conn->send(json_encode($this->userresources));
                        break;
                    default:

                        $conn->send(json_encode('Invalid message format'));
                        break;
                }
            } else {
                $conn->send(json_encode('Please pass token'));
            }
        } else {

            $conn->send(json_encode('Invalid message format'));
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        $conn->send(json_encode('Connection ' . $conn->resourceId . ' has disconnected'));
        unset($this->users[$conn->resourceId]);
        unset($this->subscriptions[$conn->resourceId]);

        foreach ($this->userresources as &$userId) {
            foreach ($userId as $key => $resourceId) {
                if ($resourceId == $conn->resourceId) {
                    unset($userId[$key]);
                }
            }
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        $conn->send(json_encode('An error has occurred ' . $e->getMessage() . ''));
        $conn->close();
    }

    /* ----get user id from token---------- */

    function checkUserToken($token) {
        $result = [];
        // break up the token_name(token)en into its three parts
        $token_parts = explode('.', $token);
        if (is_array($token_parts) && array_key_exists('1', $token_parts)) {
            $token_header = $token_parts[1];
        } else {
            $token_header = null;
        }

        // base64 decode to get a json string
        $token_header_json = base64_decode($token_header);

        // then convert the json to an array
        $token_header_array = json_decode($token_header_json, true);

        $user_token = (is_array($token_header_array) && array_key_exists('jti', $token_header_array)) ? $token_header_array['jti'] : null;
        // find the user ID from the oauth access token table
        // based on the token we just got
        if ($user_token) {
            $user_id = DB::table('oauth_access_tokens')->where('id', $user_token)->first();
            $user_id = $user_id->user_id;
            $result = [
                "user_token" => $user_token,
                "user_id" => $user_id,
            ];
            return $result;
        } else {
            return $result;
        }
    }

}

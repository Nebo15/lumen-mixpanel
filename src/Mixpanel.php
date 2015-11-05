<?php
namespace Nebo15\LumenMixpanel;


use Nebo15\LumenMixpanel\Exceptions\LumenMixpanelUserEventTypeException;

class Mixpanel extends \Mixpanel
{
    private $project_id = null;
    private $identity = null;
    private $events_execute_on_terminate = [];
    private $user_event_types = [
        'set',
        'setOnce',
        'increment',
        'append',
        'trackCharge',
    ];
    private $config;
    private $alias;
    private $ip;

    /**
     * An instance of the Mixpanel class (for singleton use)
     * @var Mixpanel
     */
    private static $_instance;

    /**
     * Instantiates a new Mixpanel instance.
     * @param $token
     * @param array $options
     */
    public function __construct($token, $options = [])
    {
        parent::__construct($token, $options);
    }


    /**
     * Returns a singleton instance of Mixpanel
     * @param $token
     * @param array $options
     * @return Mixpanel
     */
    public static function getInstance($token, $options = [])
    {
        if (!isset(self::$_instance)) {
            self::$_instance = new self($token, $options);
        }

        return self::$_instance;
    }

    /**
     * @param $project_id
     * @return $this
     */
    public function setProjectId($project_id)
    {
        $this->project_id = $project_id;

        return $this;
    }

    /**
     * @param $identity
     * @return $this
     */
    public function setIdentity($identity)
    {
        $this->identity = $identity;

        return $this;
    }

    /**
     * @param $alias
     * @return $this
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;

        return $this;
    }

    /**
     * @param $ip
     * @return $this
     */
    public function setIp($ip)
    {
        $this->ip = $ip;

        return $this;
    }

    public function sendCollectedEvents()
    {
        foreach ($this->events_execute_on_terminate as $key => $event) {
            switch ($event['event_type']) {
                case 'track':
                    $this->sendTrackEvent($event);
                    break;
                case 'user':
                    $this->sendUserEvent($event);
                    break;
                default:
                    break;
            }
            unset($this->events_execute_on_terminate[$key]);

            if ($this->alias) {
                # ToDo: check is alias already set
                try {
                    $this->createAlias($this->identity, $this->alias);
                } catch (\Exception $e) {
                }
                $this->alias = null; # flush data after mixpanel call
            }
            $this->flush();
        }
    }

    /**
     * @param $event
     */
    private function sendTrackEvent($event)
    {
        $values = $event['values'];
        if ($this->ip) {
            $values['$ip'] = $this->ip;
        }
        $this->track($event['event'], $values);
    }

    /**
     * @param $user_event
     */
    private function sendUserEvent($user_event)
    {
        switch ($user_event['type']) {
            case 'set':
                $this->people->set($this->identity, $user_event['values'], $this->ip);
                break;
            case 'setOnce':
                $this->people->setOnce($this->identity, $user_event['values']);
                break;
            case 'increment':
                $this->people->increment($this->identity, $user_event['event'], $user_event['values']);
                break;
            case 'append':
                $this->people->append($this->identity, $user_event['event'], $user_event['values']);
                break;
            case 'trackCharge':
                if (is_array($user_event['values'])) {
                    if (count($user_event['values'] == 1)) {
                        $this->people->trackCharge($this->identity, $user_event['values'][0]);
                    } else {
                        $this->people->trackCharge(
                            $this->identity,
                            $user_event['values'][0],
                            $user_event['values'][1]
                        );
                    }
                } else {
                    $this->people->trackCharge($this->identity, $user_event['values']);
                }
                break;
        }
    }

    /**
     * @param $event
     * @param $values
     * @return $this
     */
    public function addTrackEvent($event, $values = [])
    {
        $this->events_execute_on_terminate[] = [
            'event_type' => 'track',
            'event' => $this->formatEventName($event),
            'values' => $values,
        ];

        return $this;
    }

    /**
     * @param $type
     * @param $event
     * @param $values
     * @return $this
     * @throws MixpanelUserEventTypeExeption
     */
    public function addUserEvent($type, $event, $values)
    {
        if (in_array($type, $this->user_event_types)) {
            $this->events_execute_on_terminate[] = [
                'event_type' => 'user',
                'type' => $type,
                'event' => $this->formatEventName($event),
                'values' => $values,
            ];
            return $this;
        } else {
            throw new LumenMixpanelUserEventTypeException;
        }
    }

    /**
     * @param $event_name
     * @return string
     */
    private function formatEventName($event_name)
    {
        if (strpos($event_name, '-') !== false) {
            $event_name = implode(' ', array_map(function ($val) {
                return ucfirst($val);
            }, explode('-', $event_name)));
        }

        return $event_name;
    }
}
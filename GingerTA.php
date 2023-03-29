<?php 

require('./config.php');

class GingerTA{


    private $coin;
    private $fiat;
    private $pair;
    private $price = 0;
    private $direction = 0;
    private $take_profit;
    private $stop_loss;

    public $fibLevels = [
        23.6,
        38.2,
        50,
        61.8,
        78.6,
        100
    ];

    public function __construct($pair)
    {
        $this->pair = strtoupper($pair);

        $this->__setCoinFiat();
    }

    public function __setCoinFiat()
    {
        $pairArr = explode("/",$this->pair);
        
        $this->coin = $pairArr[0];
        $this->fiat = $pairArr[1];

       
    }

    public function __setCurrentPrice()
    {

        // Calll API $this->coin
        $api = "https://pro-api.coinmarketcap.com/v1/tools/price-conversion?symbol={$this->coin}&amount=1&convert={$this->fiat}&CMC_PRO_API_KEY=".API_KEY;
        $api_result = file_get_contents($api);
        $result=json_decode($api_result,true);

        $data = $result['data'] ?? null;

        if(!is_null($data)){
            $quoute = $data['quote'] ?? null;
            
            if(!is_null($quoute))
            {
                $this->price =(float) $quoute[$this->fiat]['price'];

            }
        }

    }

    public function __setDirection()
    {
        // 0 : Short 
        // 1 : Long
        $this->direction= rand(0,1);
    }

    public function calculate()
    {
        $this->__setCurrentPrice();

        if($this->price ==0)
        {
            throw new Exception('Price Not Found' );
        }

        $this->__setDirection();
        
        $fibonacci_short_prices = $this->get_fibonacci_prices(0);
        $fibonacci_long_prices = $this->get_fibonacci_prices(1);

        if($this->direction == 0)
        {
            $this->take_profit = $this->get_take_profit($fibonacci_short_prices);
            $this->stop_loss = $this->get_stop_loss($fibonacci_short_prices,$fibonacci_long_prices);
        }
        else{
            $this->take_profit = $this->get_take_profit($fibonacci_long_prices);
            $this->stop_loss = $this->get_stop_loss($fibonacci_long_prices,$fibonacci_short_prices);
        }

        return [
            'direction' => $this->direction == 0 ? 'Short' : 'Long',
            'take_profit' => $this->take_profit,
            'stop_loss' => $this->stop_loss,
            'entry' => $this->price,
            'pair' => $this->pair,
        ];
       

    }

    public function get_fibonacci_prices($direction)
    {
        $prices = [];

            foreach($this->fibLevels as $key => $value)
            {
                $diff =  ($this->price * $value ) / 1000;

                if($direction == 0)
                {
                    $price = $this->price - $diff;
                }else{
                    $price = $this->price + $diff;
                }

                $prices[] = $price;
               
                
            }

        return $prices;
    }

    public function get_take_profit($prices =[])
    {
        return $prices[array_rand($prices)];
      
      
    }

    public function get_stop_loss($tp_prices=[],$sl_prices=[])
    {
        $tp_position = array_search($this->take_profit,$tp_prices);
        
        $sl_pos = $tp_position > 1 ? $tp_position - 1 : $tp_position;

        return $sl_prices[$sl_pos];
    }


    public function get_result()
    {
        $result = $this->calculate();

        return $result;
    }

    public function get_result_json()
    {
        $result = $this->calculate();

        header("Content-Type: application/json");
        echo json_encode($result);
        exit();
    }

}
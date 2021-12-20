# Mock Input

---

The input mock simulate the input for the POST, GET or general REQUEST an setup related global $_SERVER variables


    $this->mock->input( [
        'POST' => [
            'key1' => 'value1',
            'key2' => 'value2',
            ...
        ],
        'GET' => [
            'key1' => 'value1',
            'key2' => 'value2',
            ...
        ],
        'REQUEST' => [
            'key1' => 'value1',
            'key2' => 'value2',
            ...
        ],
    ] );

# Mock Mail

---

This mock the global phpmailer to intercept all call to wp_mail and return true/false to prevent the actual email being send during testing.

Mock wp_mail send successful

    $this->mock->mail( [ 
        'send' => true,
    ] );

Mock wp_mail send failed

    $this->mock->mail( [ 
        'send' => false,
    ] );

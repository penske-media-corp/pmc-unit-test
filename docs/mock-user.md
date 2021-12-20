# Mock User

---

Mock logged in user session, in wp front page:  

    $user = $this->mock->user( true )->get();

Mock logged in admin user session, current screen in wp-admin (dashboard)

    $this->mock->user( true, 'admin' );

Mock logged in admin user session, in wp front end

    $user = $this->mock->user( 'admin' )->get();

Mock logged in admin user session, set current screen to wp-admin-page.

    $this->mock->user( 'admin', 'wp-admin-page' );


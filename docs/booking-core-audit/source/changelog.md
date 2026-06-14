## 4.0

    1. Update to Laravel 12
    2. public folder now renamed to public_html (better security for shared hosting)
        Shared hosting default directory is public_html
    3. All codes now in bc-cms folder
    4. Remove Intervention Image: it's not used in the project, too much resource usage 
    5. Fork and use package: bc/installer
    6. Fork and use package bc/qr
    7. BC Theme now will be fully livewire 3 components
    8. Template builder block now use Livewive component

## v3.4

1. Add admin account + password when install
2. Fix API login

## v3.3

1. Move admin css from resources/admin to public/themes/admin

## v3.2.1

1. Add validation for password change, now require symbol, upper and lower case and number
2. Fix issue when some social network does not have email address
3. Fix user plan report
4. [Code Improvement] Update DI for template Blocks
5. [Code Improvement] Using only search function for all query
6. Add verified badge for email in user dashboard
7. Fix upload validation issue

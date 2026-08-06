<x-mail::message>
# Password Reset Successful

Dear **{{ $user->name }}**,

Your password for the **ACETEL Thesis Monitoring System** has been reset by an administrator. You can now log in using the temporary credentials provided below.

<x-mail::panel>
## 🔐 Temporary Credentials
---
**Portal URL:** [{{ config('app.url') }}]({{ config('app.url') }})  
**Username:** `{{ $user->email }}`  
**New Password:** `{{ $password }}`
</x-mail::panel>

### 🛡️ Post-Reset Security Requirement
For your account's protection, you are **required to change this password** immediately after your next login.

<x-mail::button :url="route('login')" color="primary">
Log In & Secure Account
</x-mail::button>

*If you did not request this password reset or believe this was an error, please contact the ACETEL ICT Helpdesk immediately.*

Best regards,  
**The ACETEL Directorate**  
*Institutional Research & Digital Excellence*

<x-mail::subcopy>
You are receiving this email because an administrator reset your password on the ACETEL Thesis Monitoring System.
</x-mail::subcopy>
</x-mail::message>

<x-mail::message>
# Welcome to ACETEL Research Portal

Dear **{{ $user->name }}**,

We are pleased to inform you that your professional research account has been successfully provisioned within the **ACETEL Thesis Monitoring Ecosystem**. You now have full access to our institutional research infrastructure.

<x-mail::panel>
## 🔐 Institutional Access Credentials
---
**Portal URL:** [{{ config('app.url') }}]({{ config('app.url') }})  
**Username:** `{{ $user->email }}`  
**Temporary Password:** `{{ $password }}`
</x-mail::panel>

### 🛡️ Mandatory Security Step
For your protection and to ensure institutional security adherence, you are **required to change your password** immediately upon your first successful authentication.

<x-mail::button :url="route('login')" color="primary">
Enter Research Portal
</x-mail::button>

### 💡 What's Next?
- **Update Profile:** Ensure your contact information is current.
- **Select Thesis Project:** Begin your research journey by submitting or reviewing proposals.
- **Collaborate:** Use our integrated messaging system to connect with your supervisor or students.

*If you did not authorize the creation of this account, please contact our IT support team immediately.*

Best regards,  
**The ACETEL Directorate**  
*Institutional Research & Digital Excellence*

<x-mail::subcopy>
You are receiving this email because an account was created for you on the ACETEL Thesis Monitoring System.
</x-mail::subcopy>
</x-mail::message>

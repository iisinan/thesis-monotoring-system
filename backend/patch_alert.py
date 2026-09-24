with open('resources/views/auth/register.blade.php', 'r') as f:
    content = f.read()

# 1. Add errorMessage to form state
form_old = """
            form: {
                completed_milestones: [],
"""
form_new = """
            errorMessage: '',
            form: {
                completed_milestones: [],
"""
content = content.replace(form_old, form_new)

# 2. Add HTML for the error banner just above the fields in Step 1
html_old = """
                <div class="space-y-5">
                    <!-- Full Name -->
"""
html_new = """
                <div class="space-y-5">
                    
                    <!-- Error Banner -->
                    <div x-show="errorMessage" x-cloak class="p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-xl flex items-center gap-2 font-medium" x-transition>
                        <svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <span x-text="errorMessage"></span>
                    </div>

                    <!-- Full Name -->
"""
content = content.replace(html_old, html_new)

# 3. Update the validateStep1 function to set the errorMessage instead of calling alert()
val_old = """
            validateStep1() {
                const requiredFields = ['name', 'email', 'password', 'password_confirmation', 'matric_number', 'program_id'];
                for(let field of requiredFields) {
                    if(!document.querySelector(`[name="${field}"]`).value) {
                        alert(`Please fill all required fields before continuing.`);
                        return false;
                    }
                }
                
                if(document.querySelector(`[name="password"]`).value !== document.querySelector(`[name="password_confirmation"]`).value) {
                    alert('Passwords do not match.');
                    return false;
                }
                
                return true;
            },
"""
val_new = """
            validateStep1() {
                this.errorMessage = '';
                const requiredFields = ['name', 'email', 'password', 'password_confirmation', 'matric_number', 'program_id'];
                
                // Maps input names to readable labels for better error messages
                const fieldNames = {
                    'name': 'Full Name',
                    'email': 'Email Address',
                    'password': 'Password',
                    'password_confirmation': 'Confirm Password',
                    'matric_number': 'Matriculation Number',
                    'program_id': 'Programme'
                };

                for(let field of requiredFields) {
                    if(!document.querySelector(`[name="${field}"]`).value) {
                        this.errorMessage = `Please provide your ${fieldNames[field]} before continuing.`;
                        return false;
                    }
                }
                
                if(document.querySelector(`[name="password"]`).value !== document.querySelector(`[name="password_confirmation"]`).value) {
                    this.errorMessage = 'The passwords you entered do not match.';
                    return false;
                }
                
                return true;
            },
"""
content = content.replace(val_old.strip(), val_new.strip())

with open('resources/views/auth/register.blade.php', 'w') as f:
    f.write(content)

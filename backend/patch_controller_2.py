import re

with open('app/Http/Controllers/Auth/RegisteredUserController.php', 'r') as f:
    content = f.read()

# 1. Update validation
new_validation = """
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|confirmed|min:8',
            'matric_number' => 'required|string|max:255|unique:student_profiles,student_id_number',
            'program_id' => 'required|exists:programs,id',
"""
content = re.sub(r"'first_name' => 'required\|string\|max:255',.*?\'program_id\' => \'required\|exists:programs,id\',", new_validation.strip() + ',', content, flags=re.DOTALL)

content = re.sub(r"'level_id' => 'required\|exists:levels,id',\n", "", content)

# 2. Update user creation (name)
content = re.sub(r"'name' => trim\(\$request->first_name \. ' ' \. \$request->last_name\),", "'name' => trim($request->name),", content)

# 3. Update level_id inference
level_logic = """
            $program = \App\Models\Program::find($request->program_id);
            $isPhd = stripos($program->name, 'phd') !== false;
            
            $levelId = null;
            if ($isPhd) {
                $levelId = \App\Models\Level::where('name', 'like', '%PhD%')->value('id');
            } else {
                $levelId = \App\Models\Level::where('name', 'like', '%MSc%')->value('id');
            }

            // 3. Create StudentProfile
            $student = StudentProfile::create([
                'user_id' => $user->id,
                'student_id_number' => $matric,
                'program_id' => $request->program_id,
                'level_id' => $levelId,
"""
content = re.sub(r"// 3\. Create StudentProfile.*?\'level_id\' => \$request->level_id,", level_logic.strip(), content, flags=re.DOTALL)

with open('app/Http/Controllers/Auth/RegisteredUserController.php', 'w') as f:
    f.write(content)

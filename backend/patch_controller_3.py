with open('app/Http/Controllers/Auth/RegisteredUserController.php', 'r') as f:
    content = f.read()

# 1. Replace validation block
val_old = """
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|confirmed|min:8',
            'matric_number' => 'required|string|max:255|unique:student_profiles,student_id_number',
            'program_id' => 'required|exists:programs,id',
            'level_id' => 'required|exists:levels,id',
"""
val_new = """
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|confirmed|min:8',
            'matric_number' => 'required|string|max:255|unique:student_profiles,student_id_number',
            'program_id' => 'required|exists:programs,id',
"""
content = content.replace(val_old.strip(), val_new.strip())

# 2. Replace user creation
content = content.replace("'name' => trim($request->first_name . ' ' . $request->last_name),", "'name' => trim($request->name),")

# 3. Replace student creation
student_old = """
            // 3. Create StudentProfile
            $student = StudentProfile::create([
                'user_id' => $user->id,
                'student_id_number' => $matric,
                'program_id' => $request->program_id,
                'level_id' => $request->level_id,
"""
student_new = """
            $program = \\App\\Models\\Program::find($request->program_id);
            $isPhd = stripos($program->name, 'phd') !== false;
            
            $levelId = null;
            if ($isPhd) {
                $levelId = \\App\\Models\\Level::where('name', 'like', '%PhD%')->value('id');
            } else {
                $levelId = \\App\\Models\\Level::where('name', 'like', '%MSc%')->value('id');
            }

            // 3. Create StudentProfile
            $student = StudentProfile::create([
                'user_id' => $user->id,
                'student_id_number' => $matric,
                'program_id' => $request->program_id,
                'level_id' => $levelId,
"""
content = content.replace(student_old.strip(), student_new.strip())

with open('app/Http/Controllers/Auth/RegisteredUserController.php', 'w') as f:
    f.write(content)

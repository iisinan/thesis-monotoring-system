import os
import glob

files = [
    'resources/css/app.css',
    'resources/views/welcome.blade.php',
    'resources/views/repository/index.blade.php',
    'resources/views/repository/show.blade.php',
    'resources/views/announcements/show.blade.php',
    'resources/views/dashboard/director.blade.php',
    'resources/views/layouts/app.blade.php',
    'resources/views/layouts/admin.blade.php',
    'resources/views/layouts/dashboard.blade.php',
    'resources/views/layouts/guest.blade.php',
]

for file in files:
    if os.path.exists(file):
        with open(file, 'r') as f:
            content = f.read()
        
        # Replace Outfit with Figtree
        new_content = content.replace("family=Outfit:wght", "family=Figtree:wght")
        new_content = new_content.replace("'Outfit'", "'Figtree'")
        
        # If the file doesn't have the link but it should?
        if 'layouts/' in file and 'fonts.googleapis.com' not in new_content:
            # Let's add the link if it has a <title> tag
            if '<title>' in new_content:
                link = '    <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">\n'
                new_content = new_content.replace('<title>', link + '    <title>')

        if new_content != content:
            with open(file, 'w') as f:
                f.write(new_content)
            print(f"Updated {file}")

print("Done")

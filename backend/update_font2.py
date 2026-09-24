import os

files = [
    'resources/views/layouts/coordinator.blade.php',
]

for file in files:
    if os.path.exists(file):
        with open(file, 'r') as f:
            content = f.read()
        
        # Replace Outfit with Figtree
        new_content = content.replace("family=Outfit:wght", "family=Figtree:wght")
        new_content = new_content.replace("'Outfit'", "'Figtree'")
        
        if 'layouts/' in file and 'fonts.googleapis.com' not in new_content:
            if '<title>' in new_content:
                link = '    <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">\n'
                new_content = new_content.replace('<title>', link + '    <title>')

        if new_content != content:
            with open(file, 'w') as f:
                f.write(new_content)
            print(f"Updated {file}")

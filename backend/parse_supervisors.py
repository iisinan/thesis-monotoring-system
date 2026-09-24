import pandas as pd
import json

df = pd.read_csv('storage/app/supervisors.csv')
supervisors = []

for index, row in df.iterrows():
    name = str(row['Unnamed: 1']).strip()
    email = str(row['Unnamed: 2']).strip()
    phone = str(row['Unnamed: 3']).strip()
    
    if name == 'nan' or name == 'Supervisor Name' or name == '' or email == 'nan' or email == 'E-mail':
        continue
        
    if '@' in email:
        supervisors.append({
            'name': name,
            'email': email,
            'phone': phone if phone != 'nan' else ''
        })

with open('storage/app/supervisors.json', 'w') as f:
    json.dump(supervisors, f, indent=4)

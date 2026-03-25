import os
import hashlib
import json
import sys
from PIL import Image

def get_hash(file_path):
    hasher = hashlib.md5()
    with open(file_path, 'rb') as f:
        buf = f.read()
        hasher.update(buf)
    return hasher.hexdigest()

def optimize_and_deduplicate(folder_path):
    if not os.path.exists(folder_path):
        print(f"❌ Pasta não encontrada: {folder_path}")
        return

    print(f"🚀 A otimizar pasta: {folder_path}")
    hashes = {} 
    mapping = {} 
    
    files = sorted([f for f in os.listdir(folder_path) if f.startswith('img-') or f.startswith('page-')])
    
    for filename in files:
        filepath = os.path.join(folder_path, filename)
        
        # 1. Converter PPM para JPG se necessário
        if filename.endswith('.ppm'):
            try:
                img = Image.open(filepath)
                new_filename = filename.replace('.ppm', '.jpg')
                new_filepath = os.path.join(folder_path, new_filename)
                img.save(new_filepath, 'JPEG', quality=85)
                os.remove(filepath)
                filename = new_filename
                filepath = new_filepath
            except Exception as e:
                continue

        # 2. Deduplicação (apenas para ficheiros img-, não para page-)
        if filename.startswith('img-'):
            file_hash = get_hash(filepath)
            if file_hash in hashes:
                mapping[filename] = hashes[file_hash]
                os.remove(filepath)
            else:
                hashes[file_hash] = filename
                mapping[filename] = filename
        else:
            # Prints de página são sempre mantidos
            mapping[filename] = filename

    # 3. Guardar o mapeamento
    with open(os.path.join(folder_path, 'mapping.json'), 'w') as f:
        json.dump(mapping, f, indent=4)
    
    print(f"✅ Otimização concluída para {folder_path}")

if __name__ == "__main__":
    if len(sys.argv) > 1:
        optimize_and_deduplicate(sys.argv[1])
    else:
        print("❌ Uso: python3 optimize_images.py <caminho_da_pasta>")

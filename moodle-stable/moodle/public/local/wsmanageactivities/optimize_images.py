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
    
    # Procurar todos os ficheiros na pasta
    all_files = sorted(os.listdir(folder_path))
    
    for filename in all_files:
        filepath = os.path.join(folder_path, filename)
        
        # Ignorar mapping.json
        if filename == 'mapping.json': continue
        
        # 1. Converter formatos não suportados pelo browser (PPM, PBM, etc.)
        if filename.lower().endswith(('.ppm', '.pbm', '.pnm', '.png')): # PNM e PNG também incluídos para normalizar
            try:
                img = Image.open(filepath)
                # Converter para JPG se não for PNG (ou se quisermos tudo em JPG)
                if not filename.lower().endswith('.png'):
                    new_filename = os.path.splitext(filename)[0] + '.jpg'
                    new_filepath = os.path.join(folder_path, new_filename)
                    img.convert('RGB').save(new_filepath, 'JPEG', quality=85)
                    os.remove(filepath)
                    filename = new_filename
                    filepath = new_filepath
            except Exception as e:
                print(f"   ⚠️ Erro ao converter {filename}: {e}")
                continue

        # 2. Deduplicação (apenas para ficheiros img-)
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

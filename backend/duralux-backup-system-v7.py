#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
DURALUX CRM - Sistema de Backup Automatizado v7.0
Sistema completo de backup com agendamento, compressão e restauração

Author: Duralux Development Team
Version: 7.0
Python: 3.8+
"""

import os
import sys
import json
import shutil
import zipfile
import sqlite3
import pymysql
import schedule
import logging
import datetime
import subprocess
from pathlib import Path
from typing import Dict, List, Optional, Tuple
import hashlib
import threading
import time

class DuraluxBackupSystem:
    """Sistema completo de backup para Duralux CRM"""
    
    def __init__(self, config_file: str = "backup_config.json"):
        """
        Inicializa o sistema de backup
        
        Args:
            config_file: Arquivo de configuração JSON
        """
        self.config_file = config_file
        self.config = self.load_config()
        self.backup_history = []
        
        # Diretórios
        self.project_root = Path(__file__).parent.parent
        self.backup_dir = Path(self.config.get('backup_directory', 'backups'))
        self.backup_dir.mkdir(exist_ok=True)
        
        # Banco de dados para histórico
        self.db_file = self.backup_dir / 'backup_history.db'
        
        self.setup_logging()
        self.init_history_db()
        
        self.logger.info("🚀 Duralux Backup System v7.0 inicializado")
    
    def load_config(self) -> Dict:
        """Carrega configuração do arquivo JSON"""
        default_config = {
            "backup_directory": "backups",
            "retention_days": 30,
            "compression_level": 6,
            "schedule_time": "02:00",
            "schedule_days": ["monday", "wednesday", "friday"],
            "email_notifications": True,
            "email_recipients": [],
            "database": {
                "host": "localhost",
                "port": 3306,
                "name": "duralux_crm",
                "user": "root",
                "password": ""
            },
            "backup_types": {
                "full": True,
                "incremental": True,
                "database_only": True,
                "files_only": False
            },
            "exclude_patterns": [
                "*.log",
                "*.tmp",
                "__pycache__",
                ".git",
                "node_modules",
                "vendor",
                "*.cache"
            ],
            "include_directories": [
                "duralux-admin",
                "backend",
                "docs"
            ]
        }
        
        if os.path.exists(self.config_file):
            try:
                with open(self.config_file, 'r', encoding='utf-8') as f:
                    loaded_config = json.load(f)
                    default_config.update(loaded_config)
            except Exception as e:
                print(f"⚠️ Erro ao carregar config: {e}. Usando configuração padrão.")
        else:
            # Cria arquivo de configuração padrão
            with open(self.config_file, 'w', encoding='utf-8') as f:
                json.dump(default_config, f, indent=4, ensure_ascii=False)
            print(f"✅ Arquivo de configuração criado: {self.config_file}")
        
        return default_config
    
    def setup_logging(self):
        """Configura sistema de logging"""
        log_dir = self.backup_dir / 'logs'
        log_dir.mkdir(exist_ok=True)
        
        log_file = log_dir / f"backup_{datetime.datetime.now().strftime('%Y%m')}.log"
        
        logging.basicConfig(
            level=logging.INFO,
            format='%(asctime)s - %(levelname)s - %(message)s',
            handlers=[
                logging.FileHandler(log_file, encoding='utf-8'),
                logging.StreamHandler(sys.stdout)
            ]
        )
        self.logger = logging.getLogger(__name__)
    
    def init_history_db(self):
        """Inicializa banco de dados do histórico"""
        try:
            conn = sqlite3.connect(self.db_file)
            cursor = conn.cursor()
            
            cursor.execute('''
                CREATE TABLE IF NOT EXISTS backup_history (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    timestamp TEXT NOT NULL,
                    backup_type TEXT NOT NULL,
                    filename TEXT NOT NULL,
                    file_size INTEGER,
                    duration_seconds REAL,
                    status TEXT NOT NULL,
                    error_message TEXT,
                    checksum TEXT,
                    files_count INTEGER,
                    compressed_size INTEGER
                )
            ''')
            
            cursor.execute('''
                CREATE INDEX IF NOT EXISTS idx_timestamp 
                ON backup_history(timestamp)
            ''')
            
            cursor.execute('''
                CREATE INDEX IF NOT EXISTS idx_status 
                ON backup_history(status)
            ''')
            
            conn.commit()
            conn.close()
            
        except Exception as e:
            self.logger.error(f"Erro ao inicializar BD histórico: {e}")
    
    def calculate_checksum(self, filepath: Path) -> str:
        """Calcula checksum MD5 de um arquivo"""
        hash_md5 = hashlib.md5()
        try:
            with open(filepath, "rb") as f:
                for chunk in iter(lambda: f.read(4096), b""):
                    hash_md5.update(chunk)
            return hash_md5.hexdigest()
        except Exception as e:
            self.logger.error(f"Erro ao calcular checksum: {e}")
            return ""
    
    def get_database_connection(self) -> Optional[pymysql.Connection]:
        """Estabelece conexão com o banco MySQL"""
        try:
            db_config = self.config['database']
            connection = pymysql.connect(
                host=db_config['host'],
                port=db_config['port'],
                user=db_config['user'],
                password=db_config['password'],
                database=db_config['name'],
                charset='utf8mb4'
            )
            return connection
        except Exception as e:
            self.logger.error(f"Erro na conexão MySQL: {e}")
            return None
    
    def backup_database(self, backup_path: Path) -> Tuple[bool, str]:
        """
        Faz backup do banco de dados MySQL
        
        Returns:
            Tuple[bool, str]: (sucesso, mensagem)
        """
        try:
            db_config = self.config['database']
            dump_file = backup_path / f"database_{datetime.datetime.now().strftime('%Y%m%d_%H%M%S')}.sql"
            
            # Comando mysqldump
            cmd = [
                'mysqldump',
                '--host', db_config['host'],
                '--port', str(db_config['port']),
                '--user', db_config['user'],
                f'--password={db_config["password"]}',
                '--single-transaction',
                '--routines',
                '--triggers',
                '--add-drop-table',
                '--add-locks',
                '--extended-insert',
                db_config['name']
            ]
            
            with open(dump_file, 'w', encoding='utf-8') as f:
                result = subprocess.run(
                    cmd,
                    stdout=f,
                    stderr=subprocess.PIPE,
                    text=True,
                    timeout=3600  # 1 hora timeout
                )
            
            if result.returncode == 0:
                size = dump_file.stat().st_size
                self.logger.info(f"✅ Backup BD concluído: {dump_file} ({self.format_size(size)})")
                return True, str(dump_file)
            else:
                error_msg = result.stderr or "Erro desconhecido no mysqldump"
                self.logger.error(f"❌ Erro no backup BD: {error_msg}")
                return False, error_msg
                
        except subprocess.TimeoutExpired:
            error_msg = "Timeout no backup do banco de dados"
            self.logger.error(f"❌ {error_msg}")
            return False, error_msg
        except Exception as e:
            error_msg = f"Erro no backup BD: {str(e)}"
            self.logger.error(f"❌ {error_msg}")
            return False, error_msg
    
    def should_exclude(self, path: Path) -> bool:
        """Verifica se um arquivo/diretório deve ser excluído"""
        for pattern in self.config['exclude_patterns']:
            if path.match(pattern):
                return True
        return False
    
    def collect_files(self, directories: List[str]) -> List[Path]:
        """Coleta arquivos para backup baseado nas regras"""
        files_to_backup = []
        
        for dir_name in directories:
            dir_path = self.project_root / dir_name
            
            if not dir_path.exists():
                self.logger.warning(f"⚠️ Diretório não encontrado: {dir_path}")
                continue
            
            self.logger.info(f"📂 Coletando arquivos de: {dir_path}")
            
            for root, dirs, files in os.walk(dir_path):
                root_path = Path(root)
                
                # Filtra diretórios
                dirs[:] = [d for d in dirs if not self.should_exclude(root_path / d)]
                
                # Adiciona arquivos
                for file in files:
                    file_path = root_path / file
                    if not self.should_exclude(file_path):
                        files_to_backup.append(file_path)
        
        return files_to_backup
    
    def create_backup_archive(self, backup_type: str, files: List[Path], 
                            db_file: Optional[str] = None) -> Tuple[bool, str, Dict]:
        """
        Cria arquivo de backup comprimido
        
        Returns:
            Tuple[bool, str, Dict]: (sucesso, arquivo_backup, estatísticas)
        """
        timestamp = datetime.datetime.now()
        backup_filename = f"duralux_backup_{backup_type}_{timestamp.strftime('%Y%m%d_%H%M%S')}.zip"
        backup_filepath = self.backup_dir / backup_filename
        
        stats = {
            'files_count': 0,
            'total_size': 0,
            'compressed_size': 0,
            'compression_ratio': 0
        }
        
        try:
            compression_level = self.config.get('compression_level', 6)
            
            with zipfile.ZipFile(
                backup_filepath, 
                'w', 
                zipfile.ZIP_DEFLATED, 
                compresslevel=compression_level
            ) as zipf:
                
                # Adiciona arquivos do projeto
                for file_path in files:
                    try:
                        if file_path.exists() and file_path.is_file():
                            # Calcula path relativo para manter estrutura
                            relative_path = file_path.relative_to(self.project_root)
                            
                            zipf.write(file_path, relative_path)
                            stats['files_count'] += 1
                            stats['total_size'] += file_path.stat().st_size
                            
                            if stats['files_count'] % 100 == 0:
                                self.logger.info(f"📦 Arquivos processados: {stats['files_count']}")
                                
                    except Exception as e:
                        self.logger.warning(f"⚠️ Erro ao adicionar arquivo {file_path}: {e}")
                
                # Adiciona dump do banco se existir
                if db_file and os.path.exists(db_file):
                    db_path = Path(db_file)
                    zipf.write(db_path, f"database/{db_path.name}")
                    stats['files_count'] += 1
                    stats['total_size'] += db_path.stat().st_size
                    
                    # Remove arquivo temporário do BD
                    db_path.unlink()
            
            # Calcula estatísticas finais
            stats['compressed_size'] = backup_filepath.stat().st_size
            if stats['total_size'] > 0:
                stats['compression_ratio'] = (1 - stats['compressed_size'] / stats['total_size']) * 100
            
            self.logger.info(f"✅ Backup criado: {backup_filename}")
            self.logger.info(f"📊 Arquivos: {stats['files_count']} | "
                           f"Original: {self.format_size(stats['total_size'])} | "
                           f"Comprimido: {self.format_size(stats['compressed_size'])} | "
                           f"Compressão: {stats['compression_ratio']:.1f}%")
            
            return True, str(backup_filepath), stats
            
        except Exception as e:
            error_msg = f"Erro ao criar arquivo de backup: {str(e)}"
            self.logger.error(f"❌ {error_msg}")
            return False, error_msg, stats
    
    def perform_full_backup(self) -> Dict:
        """Executa backup completo (arquivos + banco)"""
        start_time = time.time()
        backup_type = "full"
        
        self.logger.info("🚀 Iniciando backup completo...")
        
        # Coleta arquivos
        files = self.collect_files(self.config['include_directories'])
        
        # Backup do banco
        temp_backup_dir = self.backup_dir / 'temp'
        temp_backup_dir.mkdir(exist_ok=True)
        
        db_success, db_file_or_error = self.backup_database(temp_backup_dir)
        
        # Cria arquivo de backup
        archive_success, archive_result, stats = self.create_backup_archive(
            backup_type, 
            files, 
            db_file_or_error if db_success else None
        )
        
        # Limpa diretório temporário
        shutil.rmtree(temp_backup_dir, ignore_errors=True)
        
        duration = time.time() - start_time
        
        # Resultado final
        success = db_success and archive_success
        result = {
            'success': success,
            'backup_type': backup_type,
            'duration': duration,
            'filename': archive_result if archive_success else None,
            'error': None if success else f"DB: {db_file_or_error}, Archive: {archive_result}",
            'stats': stats
        }
        
        # Registra no histórico
        self.record_backup_history(result)
        
        if success:
            self.logger.info(f"✅ Backup completo finalizado em {duration:.1f}s")
            
            # Calcula checksum
            if result['filename']:
                checksum = self.calculate_checksum(Path(result['filename']))
                result['checksum'] = checksum
        else:
            self.logger.error(f"❌ Falha no backup completo: {result['error']}")
        
        return result
    
    def perform_database_backup(self) -> Dict:
        """Executa backup apenas do banco de dados"""
        start_time = time.time()
        backup_type = "database_only"
        
        self.logger.info("🗄️ Iniciando backup do banco de dados...")
        
        # Cria diretório específico para backup BD
        db_backup_dir = self.backup_dir / 'database'
        db_backup_dir.mkdir(exist_ok=True)
        
        success, result_or_error = self.backup_database(db_backup_dir)
        duration = time.time() - start_time
        
        result = {
            'success': success,
            'backup_type': backup_type,
            'duration': duration,
            'filename': result_or_error if success else None,
            'error': result_or_error if not success else None,
            'stats': {
                'files_count': 1 if success else 0,
                'total_size': Path(result_or_error).stat().st_size if success and os.path.exists(result_or_error) else 0,
                'compressed_size': 0,
                'compression_ratio': 0
            }
        }
        
        self.record_backup_history(result)
        
        if success:
            self.logger.info(f"✅ Backup do BD finalizado em {duration:.1f}s")
        else:
            self.logger.error(f"❌ Falha no backup do BD: {result['error']}")
        
        return result
    
    def perform_files_backup(self) -> Dict:
        """Executa backup apenas dos arquivos (sem BD)"""
        start_time = time.time()
        backup_type = "files_only"
        
        self.logger.info("📁 Iniciando backup dos arquivos...")
        
        # Coleta arquivos
        files = self.collect_files(self.config['include_directories'])
        
        # Cria arquivo de backup
        success, result, stats = self.create_backup_archive(backup_type, files)
        duration = time.time() - start_time
        
        result_dict = {
            'success': success,
            'backup_type': backup_type,
            'duration': duration,
            'filename': result if success else None,
            'error': result if not success else None,
            'stats': stats
        }
        
        self.record_backup_history(result_dict)
        
        if success:
            self.logger.info(f"✅ Backup dos arquivos finalizado em {duration:.1f}s")
        else:
            self.logger.error(f"❌ Falha no backup dos arquivos: {result_dict['error']}")
        
        return result_dict
    
    def record_backup_history(self, backup_result: Dict):
        """Registra backup no histórico"""
        try:
            conn = sqlite3.connect(self.db_file)
            cursor = conn.cursor()
            
            cursor.execute('''
                INSERT INTO backup_history 
                (timestamp, backup_type, filename, file_size, duration_seconds, 
                 status, error_message, checksum, files_count, compressed_size)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ''', (
                datetime.datetime.now().isoformat(),
                backup_result['backup_type'],
                backup_result.get('filename', ''),
                backup_result['stats'].get('total_size', 0),
                backup_result['duration'],
                'success' if backup_result['success'] else 'error',
                backup_result.get('error', ''),
                backup_result.get('checksum', ''),
                backup_result['stats'].get('files_count', 0),
                backup_result['stats'].get('compressed_size', 0)
            ))
            
            conn.commit()
            conn.close()
            
        except Exception as e:
            self.logger.error(f"Erro ao registrar histórico: {e}")
    
    def cleanup_old_backups(self):
        """Remove backups antigos baseado na política de retenção"""
        retention_days = self.config.get('retention_days', 30)
        cutoff_date = datetime.datetime.now() - datetime.timedelta(days=retention_days)
        
        removed_count = 0
        freed_space = 0
        
        try:
            # Lista todos os arquivos de backup
            for backup_file in self.backup_dir.glob("duralux_backup_*.zip"):
                file_time = datetime.datetime.fromtimestamp(backup_file.stat().st_mtime)
                
                if file_time < cutoff_date:
                    file_size = backup_file.stat().st_size
                    backup_file.unlink()
                    removed_count += 1
                    freed_space += file_size
                    
                    self.logger.info(f"🗑️ Backup removido: {backup_file.name}")
            
            # Remove arquivos de BD antigos
            db_backup_dir = self.backup_dir / 'database'
            if db_backup_dir.exists():
                for db_file in db_backup_dir.glob("database_*.sql"):
                    file_time = datetime.datetime.fromtimestamp(db_file.stat().st_mtime)
                    
                    if file_time < cutoff_date:
                        file_size = db_file.stat().st_size
                        db_file.unlink()
                        removed_count += 1
                        freed_space += file_size
            
            if removed_count > 0:
                self.logger.info(f"🧹 Limpeza concluída: {removed_count} arquivos removidos, "
                               f"{self.format_size(freed_space)} liberados")
            else:
                self.logger.info("✅ Nenhum backup antigo para remover")
                
        except Exception as e:
            self.logger.error(f"Erro na limpeza de backups: {e}")
    
    def restore_backup(self, backup_file: str, restore_path: str = None) -> bool:
        """
        Restaura backup do arquivo especificado
        
        Args:
            backup_file: Caminho para o arquivo de backup
            restore_path: Diretório de destino (opcional)
        
        Returns:
            bool: Sucesso da operação
        """
        backup_path = Path(backup_file)
        
        if not backup_path.exists():
            self.logger.error(f"❌ Arquivo de backup não encontrado: {backup_file}")
            return False
        
        if restore_path is None:
            restore_path = self.project_root / f"restore_{datetime.datetime.now().strftime('%Y%m%d_%H%M%S')}"
        else:
            restore_path = Path(restore_path)
        
        restore_path.mkdir(parents=True, exist_ok=True)
        
        try:
            self.logger.info(f"🔄 Iniciando restauração de: {backup_path}")
            self.logger.info(f"📁 Destino: {restore_path}")
            
            with zipfile.ZipFile(backup_path, 'r') as zipf:
                zipf.extractall(restore_path)
            
            self.logger.info(f"✅ Restauração concluída em: {restore_path}")
            
            # Verifica se há dump do banco para restaurar
            db_files = list(restore_path.glob("**/database_*.sql"))
            if db_files:
                self.logger.info(f"🗄️ Encontrado dump do BD: {db_files[0]}")
                self.logger.info("⚠️ Para restaurar o BD, execute manualmente:")
                self.logger.info(f"mysql -u {self.config['database']['user']} -p "
                               f"{self.config['database']['name']} < {db_files[0]}")
            
            return True
            
        except Exception as e:
            self.logger.error(f"❌ Erro na restauração: {e}")
            return False
    
    def get_backup_history(self, limit: int = 50) -> List[Dict]:
        """Retorna histórico de backups"""
        try:
            conn = sqlite3.connect(self.db_file)
            cursor = conn.cursor()
            
            cursor.execute('''
                SELECT * FROM backup_history 
                ORDER BY timestamp DESC 
                LIMIT ?
            ''', (limit,))
            
            columns = [description[0] for description in cursor.description]
            history = []
            
            for row in cursor.fetchall():
                history.append(dict(zip(columns, row)))
            
            conn.close()
            return history
            
        except Exception as e:
            self.logger.error(f"Erro ao buscar histórico: {e}")
            return []
    
    def get_backup_statistics(self) -> Dict:
        """Retorna estatísticas dos backups"""
        try:
            conn = sqlite3.connect(self.db_file)
            cursor = conn.cursor()
            
            # Estatísticas gerais
            cursor.execute('''
                SELECT 
                    COUNT(*) as total_backups,
                    SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successful_backups,
                    SUM(file_size) as total_size,
                    AVG(duration_seconds) as avg_duration
                FROM backup_history
            ''')
            
            general_stats = cursor.fetchone()
            
            # Estatísticas por tipo
            cursor.execute('''
                SELECT 
                    backup_type,
                    COUNT(*) as count,
                    AVG(duration_seconds) as avg_duration,
                    SUM(file_size) as total_size
                FROM backup_history
                WHERE status = 'success'
                GROUP BY backup_type
            ''')
            
            type_stats = cursor.fetchall()
            
            # Últimos backups
            cursor.execute('''
                SELECT timestamp, backup_type, status, filename
                FROM backup_history
                ORDER BY timestamp DESC
                LIMIT 10
            ''')
            
            recent_backups = cursor.fetchall()
            
            conn.close()
            
            return {
                'total_backups': general_stats[0] or 0,
                'successful_backups': general_stats[1] or 0,
                'success_rate': (general_stats[1] / general_stats[0] * 100) if general_stats[0] > 0 else 0,
                'total_size': general_stats[2] or 0,
                'avg_duration': general_stats[3] or 0,
                'by_type': [
                    {
                        'type': row[0],
                        'count': row[1],
                        'avg_duration': row[2],
                        'total_size': row[3]
                    } for row in type_stats
                ],
                'recent_backups': [
                    {
                        'timestamp': row[0],
                        'type': row[1],
                        'status': row[2],
                        'filename': row[3]
                    } for row in recent_backups
                ]
            }
            
        except Exception as e:
            self.logger.error(f"Erro ao calcular estatísticas: {e}")
            return {}
    
    def setup_scheduled_backups(self):
        """Configura backups agendados"""
        schedule_time = self.config.get('schedule_time', '02:00')
        schedule_days = self.config.get('schedule_days', ['monday', 'wednesday', 'friday'])
        
        # Limpa agendamentos anteriores
        schedule.clear()
        
        for day in schedule_days:
            if hasattr(schedule.every(), day.lower()):
                getattr(schedule.every(), day.lower()).at(schedule_time).do(self.scheduled_backup_job)
                self.logger.info(f"📅 Backup agendado: {day}s às {schedule_time}")
        
        # Limpeza automática semanal
        schedule.every().sunday.at("03:00").do(self.cleanup_old_backups)
        self.logger.info("🧹 Limpeza automática agendada: Domingos às 03:00")
    
    def scheduled_backup_job(self):
        """Job executado pelo agendador"""
        self.logger.info("⏰ Executando backup agendado...")
        
        # Determina tipo de backup baseado no dia
        today = datetime.datetime.now().weekday()
        
        if today in [0, 2, 4]:  # Segunda, Quarta, Sexta
            backup_type = self.config['backup_types']
            
            if backup_type.get('full', True):
                result = self.perform_full_backup()
            elif backup_type.get('database_only', False):
                result = self.perform_database_backup()
            else:
                result = self.perform_files_backup()
        else:
            # Backup incremental em outros dias
            result = self.perform_files_backup()
        
        # Envia notificação se configurado
        if self.config.get('email_notifications', False):
            self.send_notification(result)
    
    def send_notification(self, backup_result: Dict):
        """Envia notificação por email do resultado do backup"""
        try:
            # Implementação básica - pode ser expandida
            recipients = self.config.get('email_recipients', [])
            if not recipients:
                return
            
            status = "✅ Sucesso" if backup_result['success'] else "❌ Falha"
            subject = f"Duralux Backup {status} - {backup_result['backup_type']}"
            
            message = f"""
Backup Report - Duralux CRM

Status: {status}
Tipo: {backup_result['backup_type']}
Duração: {backup_result['duration']:.1f}s
Arquivo: {backup_result.get('filename', 'N/A')}

Estatísticas:
- Arquivos: {backup_result['stats'].get('files_count', 0)}
- Tamanho Original: {self.format_size(backup_result['stats'].get('total_size', 0))}
- Tamanho Comprimido: {self.format_size(backup_result['stats'].get('compressed_size', 0))}

{backup_result.get('error', '') if not backup_result['success'] else ''}

Timestamp: {datetime.datetime.now()}
"""
            
            self.logger.info(f"📧 Notificação preparada: {subject}")
            # Aqui seria implementado o envio real do email
            
        except Exception as e:
            self.logger.error(f"Erro ao enviar notificação: {e}")
    
    def start_scheduler(self):
        """Inicia o agendador de backups"""
        self.setup_scheduled_backups()
        
        self.logger.info("🔄 Agendador de backups iniciado")
        
        while True:
            try:
                schedule.run_pending()
                time.sleep(60)  # Verifica a cada minuto
            except KeyboardInterrupt:
                self.logger.info("🛑 Agendador interrompido pelo usuário")
                break
            except Exception as e:
                self.logger.error(f"Erro no agendador: {e}")
                time.sleep(300)  # Aguarda 5 minutos em caso de erro
    
    def format_size(self, bytes_size: int) -> str:
        """Formata tamanho em bytes para formato legível"""
        for unit in ['B', 'KB', 'MB', 'GB', 'TB']:
            if bytes_size < 1024.0:
                return f"{bytes_size:.1f} {unit}"
            bytes_size /= 1024.0
        return f"{bytes_size:.1f} PB"
    
    def print_status(self):
        """Imprime status atual do sistema"""
        print("\n" + "="*60)
        print("🚀 DURALUX BACKUP SYSTEM v7.0 - STATUS")
        print("="*60)
        
        # Configuração atual
        print(f"📁 Diretório de Backup: {self.backup_dir}")
        print(f"⏱️ Retenção: {self.config['retention_days']} dias")
        print(f"📅 Agendamento: {self.config['schedule_days']} às {self.config['schedule_time']}")
        
        # Estatísticas
        stats = self.get_backup_statistics()
        if stats:
            print(f"\n📊 Estatísticas:")
            print(f"   Total de Backups: {stats['total_backups']}")
            print(f"   Taxa de Sucesso: {stats['success_rate']:.1f}%")
            print(f"   Espaço Total: {self.format_size(stats['total_size'])}")
            print(f"   Duração Média: {stats['avg_duration']:.1f}s")
        
        # Espaço em disco
        backup_size = sum(f.stat().st_size for f in self.backup_dir.rglob('*') if f.is_file())
        print(f"\n💾 Espaço Utilizado: {self.format_size(backup_size)}")
        
        # Próximos agendamentos
        print(f"\n⏰ Próximos Backups:")
        for job in schedule.jobs[:3]:
            print(f"   {job.next_run}")
        
        print("="*60)


def main():
    """Função principal - interface de linha de comando"""
    import argparse
    
    parser = argparse.ArgumentParser(description="Duralux CRM Backup System v7.0")
    parser.add_argument('--action', choices=['full', 'database', 'files', 'schedule', 'status', 'cleanup', 'restore'], 
                       default='status', help='Ação a executar')
    parser.add_argument('--config', default='backup_config.json', help='Arquivo de configuração')
    parser.add_argument('--restore-file', help='Arquivo de backup para restaurar')
    parser.add_argument('--restore-path', help='Diretório de destino para restauração')
    
    args = parser.parse_args()
    
    # Inicializa sistema
    backup_system = DuraluxBackupSystem(args.config)
    
    try:
        if args.action == 'full':
            print("🚀 Iniciando backup completo...")
            result = backup_system.perform_full_backup()
            if result['success']:
                print(f"✅ Backup concluído: {result['filename']}")
            else:
                print(f"❌ Falha no backup: {result['error']}")
                sys.exit(1)
                
        elif args.action == 'database':
            print("🗄️ Iniciando backup do banco de dados...")
            result = backup_system.perform_database_backup()
            if result['success']:
                print(f"✅ Backup do BD concluído: {result['filename']}")
            else:
                print(f"❌ Falha no backup do BD: {result['error']}")
                sys.exit(1)
                
        elif args.action == 'files':
            print("📁 Iniciando backup dos arquivos...")
            result = backup_system.perform_files_backup()
            if result['success']:
                print(f"✅ Backup dos arquivos concluído: {result['filename']}")
            else:
                print(f"❌ Falha no backup dos arquivos: {result['error']}")
                sys.exit(1)
                
        elif args.action == 'cleanup':
            print("🧹 Iniciando limpeza de backups antigos...")
            backup_system.cleanup_old_backups()
            
        elif args.action == 'restore':
            if not args.restore_file:
                print("❌ Especifique o arquivo de backup com --restore-file")
                sys.exit(1)
            
            success = backup_system.restore_backup(args.restore_file, args.restore_path)
            if not success:
                sys.exit(1)
                
        elif args.action == 'schedule':
            print("⏰ Iniciando agendador de backups...")
            print("Pressione Ctrl+C para parar")
            backup_system.start_scheduler()
            
        elif args.action == 'status':
            backup_system.print_status()
            
    except KeyboardInterrupt:
        print("\n🛑 Operação interrompida pelo usuário")
    except Exception as e:
        print(f"❌ Erro: {e}")
        sys.exit(1)


if __name__ == '__main__':
    main()
#!/usr/bin/env python3
"""
Setup script for Libgen WordPress Bot
"""

import os
import sys
import subprocess
import shutil

def install_requirements():
    """Install Python requirements"""
    print("🔧 Installing Python requirements...")
    try:
        subprocess.check_call([sys.executable, "-m", "pip", "install", "-r", "requirements.txt"])
        print("✅ Requirements installed successfully!")
        return True
    except subprocess.CalledProcessError as e:
        print(f"❌ Error installing requirements: {e}")
        return False

def setup_config():
    """Setup configuration file"""
    print("⚙️ Setting up configuration...")
    
    if not os.path.exists(".env"):
        if os.path.exists(".env.example"):
            shutil.copy(".env.example", ".env")
            print("✅ Configuration file created (.env)")
            print("📝 Please edit .env file with your WordPress credentials")
        else:
            print("❌ .env.example not found")
            return False
    else:
        print("⚠️ Configuration file (.env) already exists")
    
    return True

def create_directories():
    """Create necessary directories"""
    print("📁 Creating directories...")
    
    dirs = ["downloads", "logs"]
    for directory in dirs:
        os.makedirs(directory, exist_ok=True)
        print(f"✅ Created directory: {directory}")
    
    return True

def test_setup():
    """Test the setup"""
    print("🧪 Testing setup...")
    
    # Check if all required files exist
    required_files = [
        "libgen_scraper.py",
        "wordpress_uploader.py", 
        "libgen_bot.py",
        ".env"
    ]
    
    for file in required_files:
        if not os.path.exists(file):
            print(f"❌ Required file missing: {file}")
            return False
        print(f"✅ Found: {file}")
    
    print("✅ Setup completed successfully!")
    print("\n📖 Next steps:")
    print("1. Edit .env file with your WordPress credentials")
    print("2. Test connection: python libgen_bot.py --test")
    print("3. Run your first search: python libgen_bot.py --query 'python programming' --max-results 2")
    
    return True

def main():
    """Main setup function"""
    print("🚀 Libgen WordPress Bot Setup")
    print("=" * 40)
    
    steps = [
        ("Installing requirements", install_requirements),
        ("Setting up configuration", setup_config),
        ("Creating directories", create_directories),
        ("Testing setup", test_setup)
    ]
    
    for step_name, step_func in steps:
        print(f"\n{step_name}...")
        if not step_func():
            print(f"❌ Setup failed at: {step_name}")
            return 1
    
    print("\n🎉 Setup completed successfully!")
    return 0

if __name__ == "__main__":
    sys.exit(main())
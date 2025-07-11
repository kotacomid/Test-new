#!/usr/bin/env python3
"""
SEO Link Building Tool - Easy Startup Script
Automatically sets up and starts the SEO link building dashboard
"""

import os
import sys
import subprocess
import webbrowser
from time import sleep

def check_python_version():
    """Check if Python version is compatible"""
    if sys.version_info < (3, 7):
        print("❌ Python 3.7 or higher is required")
        print(f"   Current version: {sys.version}")
        return False
    print(f"✅ Python {sys.version.split()[0]} detected")
    return True

def install_requirements():
    """Install required packages"""
    print("📦 Installing required packages...")
    try:
        subprocess.check_call([sys.executable, "-m", "pip", "install", "-r", "requirements.txt"])
        print("✅ All packages installed successfully")
        return True
    except subprocess.CalledProcessError as e:
        print(f"❌ Error installing packages: {e}")
        return False

def check_files_exist():
    """Check if all required files exist"""
    required_files = [
        "seo_link_builder.py",
        "web_interface.py", 
        "requirements.txt",
        "templates/dashboard.html"
    ]
    
    missing_files = []
    for file in required_files:
        if not os.path.exists(file):
            missing_files.append(file)
    
    if missing_files:
        print("❌ Missing required files:")
        for file in missing_files:
            print(f"   - {file}")
        return False
    
    print("✅ All required files found")
    return True

def start_application():
    """Start the web application"""
    print("🚀 Starting SEO Link Building Dashboard...")
    print("📊 Dashboard will be available at: http://localhost:5000")
    print("⏳ Please wait a moment for the server to start...")
    
    try:
        # Start the web interface
        process = subprocess.Popen([sys.executable, "web_interface.py"])
        
        # Wait a moment for the server to start
        sleep(3)
        
        # Try to open the browser
        try:
            webbrowser.open("http://localhost:5000")
            print("🌐 Browser opened automatically")
        except:
            print("💡 Please manually open: http://localhost:5000")
        
        print("\n" + "="*50)
        print("🎯 SEO Link Building Tool is now running!")
        print("="*50)
        print("📖 Quick Start Guide:")
        print("1. Enter your target keywords")
        print("2. Add competitor URLs (optional)")
        print("3. Click 'Start Search' to find opportunities")
        print("4. Use 'Outreach' to generate personalized emails")
        print("5. Track your progress and results")
        print("\n💡 Tips:")
        print("- Focus on high Domain Authority sites (40+)")
        print("- Personalize every outreach email")
        print("- Build relationships, not just links")
        print("- Follow up politely after 1-2 weeks")
        print("\n🛑 To stop the server: Press Ctrl+C")
        print("="*50)
        
        # Keep the process running
        process.wait()
        
    except KeyboardInterrupt:
        print("\n🛑 Shutting down SEO Link Building Tool...")
        process.terminate()
        print("✅ Server stopped successfully")
    except Exception as e:
        print(f"❌ Error starting application: {e}")

def main():
    """Main startup function"""
    print("🔗 SEO Link Building Tool - Startup Script")
    print("==========================================")
    
    # Check Python version
    if not check_python_version():
        return
    
    # Check if required files exist
    if not check_files_exist():
        print("\n💡 Please ensure all files are in the correct location:")
        print("   - seo_link_builder.py")
        print("   - web_interface.py")
        print("   - requirements.txt")
        print("   - templates/dashboard.html")
        return
    
    # Install requirements
    print("\n📋 Checking dependencies...")
    if not install_requirements():
        print("💡 Try running: pip install -r requirements.txt")
        return
    
    print("\n🎉 Setup complete! Starting the application...")
    sleep(1)
    
    # Start the application
    start_application()

if __name__ == "__main__":
    main()
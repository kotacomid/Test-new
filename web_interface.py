#!/usr/bin/env python3
"""
SEO Link Building Web Interface
A web dashboard for managing link building campaigns
"""

from flask import Flask, render_template, request, jsonify, send_file
from flask_cors import CORS
import json
import os
from datetime import datetime
from seo_link_builder import SEOLinkBuilder, OutreachTemplates
import sqlite3
import pandas as pd

app = Flask(__name__)
CORS(app)

# Initialize the link builder
link_builder = SEOLinkBuilder()

@app.route('/')
def dashboard():
    """Main dashboard page"""
    return render_template('dashboard.html')

@app.route('/api/opportunities')
def get_opportunities():
    """API endpoint to get link opportunities"""
    status = request.args.get('status')
    min_authority = int(request.args.get('min_authority', 0))
    
    opportunities = link_builder.get_opportunities(status=status, min_authority=min_authority)
    return jsonify(opportunities)

@app.route('/api/opportunities/search', methods=['POST'])
def search_opportunities():
    """API endpoint to search for new opportunities"""
    data = request.get_json()
    keywords = data.get('keywords', [])
    competitors = data.get('competitors', [])
    search_types = data.get('search_types', ['guest_posting'])
    
    all_opportunities = []
    
    try:
        if 'guest_posting' in search_types:
            guest_opportunities = link_builder.find_guest_posting_opportunities(keywords, max_results=20)
            all_opportunities.extend(guest_opportunities)
        
        if 'resource_pages' in search_types:
            resource_opportunities = link_builder.find_resource_page_opportunities(keywords)
            all_opportunities.extend(resource_opportunities)
        
        if 'broken_links' in search_types and competitors:
            broken_opportunities = link_builder.find_broken_link_opportunities(competitors)
            all_opportunities.extend(broken_opportunities)
        
        if all_opportunities:
            link_builder.save_opportunities(all_opportunities)
        
        return jsonify({
            'success': True,
            'count': len(all_opportunities),
            'opportunities': all_opportunities
        })
    
    except Exception as e:
        return jsonify({
            'success': False,
            'error': str(e)
        }), 500

@app.route('/api/outreach/generate', methods=['POST'])
def generate_outreach():
    """Generate personalized outreach email"""
    data = request.get_json()
    
    opportunity = data.get('opportunity')
    template_name = data.get('template_name')
    your_name = data.get('your_name')
    your_site = data.get('your_site')
    content_url = data.get('content_url')
    
    try:
        email = link_builder.generate_outreach_email(
            opportunity, template_name, your_name, your_site, content_url
        )
        return jsonify({
            'success': True,
            'email': email
        })
    except Exception as e:
        return jsonify({
            'success': False,
            'error': str(e)
        }), 500

@app.route('/api/outreach/track', methods=['POST'])
def track_outreach():
    """Track outreach attempt"""
    data = request.get_json()
    
    opportunity_id = data.get('opportunity_id')
    status = data.get('status')
    notes = data.get('notes', '')
    
    try:
        link_builder.track_outreach(opportunity_id, status, notes)
        return jsonify({'success': True})
    except Exception as e:
        return jsonify({
            'success': False,
            'error': str(e)
        }), 500

@app.route('/api/export/csv')
def export_csv():
    """Export opportunities to CSV"""
    status = request.args.get('status')
    filename = f"link_opportunities_{datetime.now().strftime('%Y%m%d_%H%M%S')}.csv"
    
    try:
        link_builder.export_opportunities(filename, status=status)
        return send_file(filename, as_attachment=True, download_name=filename)
    except Exception as e:
        return jsonify({
            'success': False,
            'error': str(e)
        }), 500

@app.route('/api/stats')
def get_stats():
    """Get dashboard statistics"""
    try:
        conn = sqlite3.connect(link_builder.db_file)
        cursor = conn.cursor()
        
        # Total opportunities
        cursor.execute("SELECT COUNT(*) FROM opportunities")
        total_opportunities = cursor.fetchone()[0]
        
        # By status
        cursor.execute("SELECT outreach_status, COUNT(*) FROM opportunities GROUP BY outreach_status")
        status_counts = dict(cursor.fetchall())
        
        # Average domain authority
        cursor.execute("SELECT AVG(domain_authority) FROM opportunities")
        avg_da = cursor.fetchone()[0] or 0
        
        # Recent opportunities (last 7 days)
        cursor.execute("""
            SELECT COUNT(*) FROM opportunities 
            WHERE date_found >= datetime('now', '-7 days')
        """)
        recent_opportunities = cursor.fetchone()[0]
        
        conn.close()
        
        return jsonify({
            'total_opportunities': total_opportunities,
            'status_counts': status_counts,
            'average_domain_authority': round(avg_da, 1),
            'recent_opportunities': recent_opportunities
        })
    
    except Exception as e:
        return jsonify({
            'success': False,
            'error': str(e)
        }), 500

@app.route('/api/templates')
def get_templates():
    """Get available email templates"""
    try:
        conn = sqlite3.connect(link_builder.db_file)
        cursor = conn.cursor()
        
        cursor.execute("SELECT template_name, template_type FROM outreach_templates")
        templates = [{'name': row[0], 'type': row[1]} for row in cursor.fetchall()]
        
        conn.close()
        return jsonify(templates)
    
    except Exception as e:
        return jsonify({
            'success': False,
            'error': str(e)
        }), 500

if __name__ == '__main__':
    # Create templates directory if it doesn't exist
    if not os.path.exists('templates'):
        os.makedirs('templates')
    
    # Setup default templates
    templates = OutreachTemplates()
    
    guest_template = templates.guest_posting_template()
    link_builder.create_outreach_template(
        guest_template['name'],
        guest_template['subject'],
        guest_template['body'],
        guest_template['type']
    )
    
    broken_template = templates.broken_link_template()
    link_builder.create_outreach_template(
        broken_template['name'],
        broken_template['subject'],
        broken_template['body'],
        broken_template['type']
    )
    
    resource_template = templates.resource_page_template()
    link_builder.create_outreach_template(
        resource_template['name'],
        resource_template['subject'],
        resource_template['body'],
        resource_template['type']
    )
    
    print("🚀 Starting SEO Link Building Dashboard...")
    print("📊 Dashboard available at: http://localhost:5000")
    print("🔧 API endpoints available at: http://localhost:5000/api/")
    
    app.run(debug=True, host='0.0.0.0', port=5000)
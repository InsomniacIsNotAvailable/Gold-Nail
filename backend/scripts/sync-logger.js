/**
 * Gold OHLC Sync Logger
 * Provides detailed console logging for date checking and sync operations
 */
(function (global) {
  'use strict';

  // Derive base prefix for API calls
  let BASE_PREFIX = '';
  try {
    const script = document.currentScript;
    const srcPath = script ? new URL(script.src, global.location.href).pathname : global.location.pathname;
    const idx = srcPath.toLowerCase().indexOf('/backend/');
    BASE_PREFIX = idx > -1 ? srcPath.slice(0, idx) : (srcPath.substring(0, srcPath.lastIndexOf('/')) || '');
  } catch {}

  // Utility functions
  function toUtcYmd(d) {
    const y = d.getUTCFullYear();
    const m = String(d.getUTCMonth() + 1).padStart(2, '0');
    const day = String(d.getUTCDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
  }

  function addDaysUtc(ymd, delta) {
    const d = new Date(ymd + 'T00:00:00Z');
    d.setUTCDate(d.getUTCDate() + delta);
    return toUtcYmd(d);
  }

  function formatTimestamp() {
    return new Date().toISOString().replace('T', ' ').substring(0, 19);
  }

  // Enhanced sync function with detailed logging
  async function ensureSyncedWithLogging() {
    const timestamp = formatTimestamp();
    console.group(`🔄 [${timestamp}] Gold OHLC Sync Check Started`);
    
    try {
      // Step 1: Get current date info
      const now = new Date();
      const currentDate = toUtcYmd(now);
      const targetEnd = new Date(Date.UTC(
        now.getUTCFullYear(),
        now.getUTCMonth(),
        now.getUTCDate() - 1
      ));
      const yesterdayDate = toUtcYmd(targetEnd);
      
      console.log(`📅 Current date (UTC): ${currentDate}`);
      console.log(`📅 Target sync end date (yesterday UTC): ${yesterdayDate}`);
      
      // Step 2: Fetch last date from database
      console.log('📡 Fetching latest date from database...');
      const lastRes = await fetch(`${BASE_PREFIX}/backend/api/gold_ohlc_sync.php?action=lastDate`);
      
      if (!lastRes.ok) {
        console.warn(`❌ Failed to fetch last date: HTTP ${lastRes.status}`);
        console.groupEnd();
        return;
      }
      
      const lastJson = await lastRes.json();
      const lastDbDate = lastJson?.last || null;
      
      if (!lastDbDate) {
        console.log('💡 No data found in database - skipping automatic sync');
        console.log('ℹ️  Manual initial backfill required via console commands');
        console.groupEnd();
        return;
      }
      
      console.log(`💾 Latest date in database: ${lastDbDate}`);
      
      // Step 3: Calculate sync range
      const syncFromDate = addDaysUtc(lastDbDate, 1);
      const syncToDate = yesterdayDate;
      
      console.log(`🔍 Checking if sync needed:`);
      console.log(`   From: ${syncFromDate}`);
      console.log(`   To: ${syncToDate}`);
      console.log(`   Condition: ${syncFromDate} <= ${syncToDate} = ${syncFromDate <= syncToDate}`);
      
      // Step 4: Determine if sync is needed
      if (syncFromDate > syncToDate) {
        console.log('✅ Database is up to date - no sync needed');
        console.groupEnd();
        return;
      }
      
      // Calculate how many days behind
      const fromDateObj = new Date(syncFromDate + 'T00:00:00Z');
      const toDateObj = new Date(syncToDate + 'T00:00:00Z');
      const daysBehind = Math.ceil((toDateObj - fromDateObj) / (1000 * 60 * 60 * 24)) + 1;
      
      console.log(`⚠️  Database is ${daysBehind} day(s) behind`);
      console.log('🚀 Starting automatic sync...');
      
      // Step 5: Perform sync
      const syncStartTime = Date.now();
      const syncRes = await fetch(`${BASE_PREFIX}/backend/api/gold_ohlc_sync.php?action=syncRange`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ from: syncFromDate, to: syncToDate })
      });
      
      const syncDuration = Date.now() - syncStartTime;
      
      if (!syncRes.ok) {
        console.error(`❌ Sync failed: HTTP ${syncRes.status}`);
        const errorText = await syncRes.text().catch(() => 'Unknown error');
        console.error(`   Error details: ${errorText}`);
        console.groupEnd();
        throw new Error(`syncRange failed: ${syncRes.status}`);
      }
      
      const syncResult = await syncRes.json();
      console.log(`✅ Sync completed successfully in ${syncDuration}ms`);
      
      if (syncResult.synced) {
        console.log(`📊 Records synced: ${syncResult.synced}`);
      }
      if (syncResult.errors && syncResult.errors.length > 0) {
        console.warn(`⚠️  Sync completed with ${syncResult.errors.length} error(s):`);
        syncResult.errors.forEach((error, index) => {
          console.warn(`   ${index + 1}. ${error}`);
        });
      }
      
      console.groupEnd();
      return syncResult;
      
    } catch (error) {
      console.error('❌ Sync check failed:', error);
      console.groupEnd();
      throw error;
    }
  }

  // Sync status checker
  async function checkSyncStatus() {
    const timestamp = formatTimestamp();
    console.group(`📊 [${timestamp}] Gold OHLC Sync Status Check`);
    
    try {
      // Get last date
      const lastRes = await fetch(`${BASE_PREFIX}/backend/api/gold_ohlc_sync.php?action=lastDate`);
      const lastJson = lastRes.ok ? await lastRes.json() : { last: null };
      const lastDate = lastJson?.last || null;
      
      const now = new Date();
      const currentDate = toUtcYmd(now);
      const yesterdayDate = toUtcYmd(new Date(Date.UTC(
        now.getUTCFullYear(),
        now.getUTCMonth(),
        now.getUTCDate() - 1
      )));
      
      console.log(`📅 Current date: ${currentDate}`);
      console.log(`📅 Yesterday: ${yesterdayDate}`);
      console.log(`💾 Last DB date: ${lastDate || 'No data'}`);
      
      if (!lastDate) {
        console.log('🔴 Status: Empty database - manual backfill required');
      } else if (lastDate >= yesterdayDate) {
        console.log('🟢 Status: Up to date');
      } else {
        const daysBehind = Math.ceil((new Date(yesterdayDate) - new Date(lastDate)) / (1000 * 60 * 60 * 24));
        console.log(`🟡 Status: ${daysBehind} day(s) behind`);
      }
      
      console.groupEnd();
      return { lastDate, currentDate, yesterdayDate };
      
    } catch (error) {
      console.error('❌ Status check failed:', error);
      console.groupEnd();
      throw error;
    }
  }

  // Monitor sync activity
  function startSyncMonitoring(intervalMinutes = 5) {
    const interval = intervalMinutes * 60 * 1000;
    console.log(`🔍 Starting sync monitoring (checking every ${intervalMinutes} minutes)`);
    
    // Initial check
    checkSyncStatus();
    
    // Periodic checks
    return setInterval(() => {
      checkSyncStatus();
    }, interval);
  }

  // Expose functions to global scope
  global.GoldSyncLogger = {
    ensureSyncedWithLogging,
    checkSyncStatus,
    startSyncMonitoring,
    
    // Utility functions
    toUtcYmd,
    addDaysUtc,
    formatTimestamp
  };

  // Auto-log sync check on page load
  document.addEventListener('DOMContentLoaded', () => {
    console.log('🌟 Gold Sync Logger initialized');
    console.log('💡 Available commands:');
    console.log('   - GoldSyncLogger.ensureSyncedWithLogging() - Run sync check with detailed logging');
    console.log('   - GoldSyncLogger.checkSyncStatus() - Check current sync status');
    console.log('   - GoldSyncLogger.startSyncMonitoring(5) - Start monitoring every 5 minutes');
  });

})(window);
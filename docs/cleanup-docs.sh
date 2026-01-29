#!/bin/bash
# Documentation Cleanup Script
# Organizes docs/ folder into a clean structure

set -e  # Exit on error

DOCS_DIR="/Users/chris.majorossy/Education/8pm/docs"
cd "$DOCS_DIR"

echo "📚 Starting documentation cleanup..."
echo ""

# Create archive structure
echo "Creating archive directories..."
mkdir -p archive/completed
mkdir -p archive/testing
mkdir -p implementations

# Track what we're moving
MOVED_LOG="archive/CLEANUP_LOG_$(date +%Y%m%d_%H%M%S).md"

cat > "$MOVED_LOG" << 'EOF'
# Documentation Cleanup Log

**Date:** $(date)
**Purpose:** Organize docs/ folder for better navigation

## Archive Structure Created

```
docs/
├── archive/
│   ├── completed/      # Completion reports and verification docs
│   ├── testing/        # Test plans and reports
│   └── import-rearchitecture/  # Phase plans (100% complete)
└── implementations/    # Feature implementation guides
```

## Files Moved

EOF

echo "# Completion Reports" >> "$MOVED_LOG"
echo "" >> "$MOVED_LOG"

# Move completion/verification files
echo "Moving completion reports..."
for file in *COMPLETE*.md *VERIFICATION*.md *CONSOLIDATION*.md; do
  if [ -f "$file" ] && [ "$file" != "CLEANUP_LOG"* ]; then
    echo "  → archive/completed/$file"
    echo "- $file → archive/completed/" >> "$MOVED_LOG"
    git mv "$file" "archive/completed/" 2>/dev/null || mv "$file" "archive/completed/"
  fi
done

echo "" >> "$MOVED_LOG"
echo "# Test Reports" >> "$MOVED_LOG"
echo "" >> "$MOVED_LOG"

# Move test files
echo "Moving test reports..."
for file in *TEST*.md PHASE*SUMMARY*.md; do
  if [ -f "$file" ]; then
    echo "  → archive/testing/$file"
    echo "- $file → archive/testing/" >> "$MOVED_LOG"
    git mv "$file" "archive/testing/" 2>/dev/null || mv "$file" "archive/testing/"
  fi
done

echo "" >> "$MOVED_LOG"
echo "# Implementation Guides" >> "$MOVED_LOG"
echo "" >> "$MOVED_LOG"

# Move implementation docs
echo "Moving implementation guides..."
for file in *IMPLEMENTATION*.md; do
  if [ -f "$file" ]; then
    echo "  → implementations/$file"
    echo "- $file → implementations/" >> "$MOVED_LOG"
    git mv "$file" "implementations/" 2>/dev/null || mv "$file" "implementations/"
  fi
done

echo "" >> "$MOVED_LOG"
echo "# Import Rearchitecture (Complete)" >> "$MOVED_LOG"
echo "" >> "$MOVED_LOG"

# Move import-rearchitecture folder
if [ -d "import-rearchitecture" ]; then
  echo "Moving import-rearchitecture/ folder..."
  echo "  → archive/import-rearchitecture/"
  echo "- import-rearchitecture/ → archive/ (entire folder)" >> "$MOVED_LOG"
  git mv "import-rearchitecture" "archive/" 2>/dev/null || mv "import-rearchitecture" "archive/"
fi

echo "" >> "$MOVED_LOG"
echo "# Files Kept in Root" >> "$MOVED_LOG"
echo "" >> "$MOVED_LOG"
echo "Reference guides and active plans:" >> "$MOVED_LOG"
echo "" >> "$MOVED_LOG"

# List what's left in root
for file in *.md; do
  if [ -f "$file" ] && [ "$file" != "cleanup-docs.sh" ]; then
    echo "- $file" >> "$MOVED_LOG"
  fi
done

echo "" >> "$MOVED_LOG"
echo "## Next Steps" >> "$MOVED_LOG"
echo "" >> "$MOVED_LOG"
echo "1. Review archive/ folders for any docs you still need" >> "$MOVED_LOG"
echo "2. Update CLAUDE.md references if needed" >> "$MOVED_LOG"
echo "3. Consider consolidating similar implementation docs" >> "$MOVED_LOG"
echo "4. Commit changes: \`git add . && git commit -m 'docs: organize documentation structure'\`" >> "$MOVED_LOG"

# Create README in archive
cat > "archive/README.md" << 'EOF'
# Archived Documentation

This folder contains historical documentation that has been completed or superseded.

## Structure

- **completed/** - Completion reports, verification docs, fix summaries
- **testing/** - Test plans, test reports, benchmarks
- **import-rearchitecture/** - Phase plans for import system (100% complete)

## Why Archive?

These docs are kept for:
- Historical context of decisions made
- Understanding implementation approach
- Troubleshooting reference
- Learning from past work

## Need Something?

If you need to reference archived work:
1. Check the CLEANUP_LOG files for what was moved when
2. Search by filename or grep content
3. If a doc is still relevant, consider moving it back to root or implementations/
EOF

# Create README in implementations
cat > "implementations/README.md" << 'EOF'
# Implementation Guides

Detailed guides for how major features were implemented.

These docs explain:
- Technical architecture decisions
- How features work under the hood
- Integration points
- Configuration options
- Troubleshooting tips

## Active Implementations

See files in this directory for specific feature implementations.

## Reference

For general guides, see parent docs/ folder:
- ADMIN_GUIDE.md
- DEVELOPER_GUIDE.md
- COMMAND_GUIDE.md
- API.md
EOF

echo ""
echo "✅ Cleanup complete!"
echo ""
echo "Summary:"
echo "  • Moved completion reports → archive/completed/"
echo "  • Moved test reports → archive/testing/"
echo "  • Moved implementation guides → implementations/"
echo "  • Moved import-rearchitecture/ → archive/"
echo ""
echo "Files remaining in docs/:"
ls -1 *.md 2>/dev/null | grep -v cleanup-docs.sh | wc -l | xargs echo "  •"
echo ""
echo "📄 Cleanup log: $MOVED_LOG"
echo ""
echo "Next: Review changes and commit with git"
